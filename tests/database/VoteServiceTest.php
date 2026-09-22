<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\VoteResult;
use App\Services\VoterType;
use App\Services\VoteService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Stage 2: transaction, penguncian, dan race condition VoteService langsung
 * terhadap MySQL/MariaDB (tanpa lapisan HTTP).
 *
 * Test paralel memakai pcntl_fork (Linux/macOS). Di Windows/Laragon ekstensi
 * pcntl tidak tersedia sehingga test tersebut dilewati (skipped), test lain
 * tetap berjalan.
 *
 * @internal
 */
final class VoteServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function service(): VoteService
    {
        return new VoteService($this->db);
    }

    private function lockedCount(string $table, string $column, int $voterId): int
    {
        return $this->db->table($table)->where($column, $voterId)->where('status', 'LOCKED')->countAllResults();
    }

    public function testCastVoteStoresOneLockedRow(): void
    {
        $result = $this->service()->castVote(VoterType::Student, 1, 2, ['device' => 'HP / Android', 'browser' => 'Chrome 126']);

        $this->assertTrue($result->isOk());
        $this->assertSame(200, $result->httpStatus());
        $this->assertSame('Bagas Prayoga', $result->vote['candidate']['nama_ketua']);
        $this->seeInDatabase('student_votes', [
            'id'           => $result->vote['id'],
            'student_id'   => 1,
            'candidate_id' => 2,
            'status'       => 'LOCKED',
            'device_info'  => 'HP / Android',
            'browser_info' => 'Chrome 126',
        ]);
        $this->assertSame(0, $this->db->transDepth);
    }

    public function testRejectionRollsBackAndKeepsConnectionUsable(): void
    {
        $closed = $this->service()->castVote(VoterType::Student, 1, 1, [], Time::parse('2000-01-01 00:00:00'));

        $this->assertSame(VoteResult::VOTING_CLOSED, $closed->status);
        $this->assertSame('UPCOMING', $closed->electionStatus);
        $this->assertSame(403, $closed->httpStatus());
        $this->assertSame(0, $this->db->transDepth);
        $this->assertTrue($this->db->transStatus());

        $this->assertSame(VoteResult::INVALID_CANDIDATE, $this->service()->castVote(VoterType::Student, 1, 999)->status);
        $this->assertSame(VoteResult::VOTER_INACTIVE, $this->service()->castVote(VoterType::Student, 12, 1)->status);

        $this->assertTrue($this->service()->castVote(VoterType::Student, 1, 1)->isOk());
    }

    public function testNoElectionIsRejected(): void
    {
        $this->db->disableForeignKeyChecks();
        $this->db->table('elections')->truncate();
        $this->db->enableForeignKeyChecks();

        $this->assertSame(VoteResult::NO_ELECTION, $this->service()->castVote(VoterType::Teacher, 1, 1)->status);
    }

    /**
     * Pengecekan awal "sudah memilih" dilewati (seperti dua request yang lolos
     * pengecekan bersamaan): unique key uq_student_votes_active harus menolak
     * dan hasilnya "sudah memilih", bukan error 500.
     */
    public function testUniqueKeyConflictIsReportedAsAlreadyVoted(): void
    {
        $this->assertTrue($this->service()->castVote(VoterType::Student, 1, 1)->isOk());

        $racy = new class ($this->db) extends VoteService {
            protected function findActiveVoteId(VoterType $type, int $electionId, int $voterId): ?int
            {
                return null;
            }
        };

        $result = $racy->castVote(VoterType::Student, 1, 3);

        $this->assertSame(VoteResult::ALREADY_VOTED, $result->status);
        $this->assertSame(409, $result->httpStatus());
        $this->assertSame(0, $this->db->transDepth);
        $this->assertTrue($this->db->transStatus());
        $this->assertSame(1, $this->lockedCount('student_votes', 'student_id', 1));
        $this->seeInDatabase('student_votes', ['student_id' => 1, 'candidate_id' => 1, 'status' => 'LOCKED']);

        // Koneksi tetap sehat untuk pemilih berikutnya.
        $this->assertTrue($racy->castVote(VoterType::Teacher, 1, 3)->isOk());
    }

    /**
     * Baris pemilih sedang dikunci transaction lain terlalu lama: hasilnya
     * RETRY (503, "kirim ulang"), bukan error 500 dan tanpa suara tersimpan.
     */
    public function testLockWaitTimeoutIsReportedAsRetry(): void
    {
        $other = Database::connect('tests', false);
        $other->transBegin();
        $other->query('SELECT id FROM students WHERE id = 1 FOR UPDATE');

        $this->db->query('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $result = $this->service()->castVote(VoterType::Student, 1, 1);
        } finally {
            $this->db->query('SET SESSION innodb_lock_wait_timeout = 50');
            $other->transRollback();
            $other->close();
        }

        $this->assertSame(VoteResult::RETRY, $result->status);
        $this->assertSame(503, $result->httpStatus());
        $this->assertSame(0, $this->lockedCount('student_votes', 'student_id', 1));
        $this->assertSame(0, $this->db->transDepth);
        $this->assertTrue($this->service()->castVote(VoterType::Student, 1, 1)->isOk());
    }

    public function testBallotStateOnlyContainsOwnVote(): void
    {
        $this->service()->castVote(VoterType::Student, 1, 2);
        $this->service()->castVote(VoterType::Student, 2, 3);

        $own = $this->service()->ballotState(VoterType::Student, 1);
        $this->assertSame('2', (string) $own['vote']['candidate_id']);
        $this->assertSame('Bagas Prayoga', $own['candidate']['nama_ketua']);
        $this->assertFalse($own['canVote']);

        // Guru dengan id yang sama tidak terpengaruh suara siswa.
        $teacher = $this->service()->ballotState(VoterType::Teacher, 1);
        $this->assertNull($teacher['vote']);
        $this->assertTrue($teacher['canVote']);
    }

    // ------------------------------------------------------------------
    // Race condition nyata: beberapa proses PHP dengan koneksi database
    // sendiri-sendiri mengirim suara pada saat yang sama.
    // ------------------------------------------------------------------

    public function testParallelRequestsFromOneStudentCreateExactlyOneVote(): void
    {
        $jobs = [];
        for ($i = 0; $i < 6; $i++) {
            $jobs[] = [VoterType::Student, 3, ($i % 3) + 1];
        }

        $results = $this->runInParallel($jobs);

        $this->assertSame(1, count(array_keys($results, VoteResult::OK, true)), implode(', ', $results));
        $this->assertSame(5, count(array_keys($results, VoteResult::ALREADY_VOTED, true)), implode(', ', $results));
        $this->assertSame(1, $this->lockedCount('student_votes', 'student_id', 3));
        $this->assertSame(1, $this->db->table('student_votes')->countAllResults());
    }

    public function testParallelVotesFromDifferentVotersAllSucceedWithoutDeadlock(): void
    {
        $jobs = [];
        for ($id = 1; $id <= 8; $id++) {
            $jobs[] = [VoterType::Student, $id, ($id % 3) + 1];
        }
        for ($id = 1; $id <= 4; $id++) {
            $jobs[] = [VoterType::Teacher, $id, ($id % 3) + 1];
        }

        $results = $this->runInParallel($jobs);

        $this->assertSame(array_fill(0, 12, VoteResult::OK), $results);
        $this->assertSame(8, $this->db->table('student_votes')->where('status', 'LOCKED')->countAllResults());
        $this->assertSame(4, $this->db->table('teacher_votes')->where('status', 'LOCKED')->countAllResults());
    }

    /**
     * @param list<array{0: VoterType, 1: int, 2: int}> $jobs
     *
     * @return list<string> status VoteResult per job (urutan sama dengan $jobs)
     */
    private function runInParallel(array $jobs): array
    {
        if (! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('Ekstensi pcntl/posix tidak tersedia (mis. Windows/Laragon).');
        }

        $dir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vote-race-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $startAt = microtime(true) + 0.8;
        $pids    = [];

        foreach ($jobs as $index => [$type, $voterId, $candidateId]) {
            $pid = pcntl_fork();

            if ($pid === -1) {
                $this->fail('pcntl_fork gagal');
            }

            if ($pid === 0) {
                // Proses anak: koneksi database baru (bukan milik proses induk).
                $status = 'error';

                try {
                    $service = new VoteService(Database::connect('tests', false));

                    while (microtime(true) < $startAt) {
                        usleep(500);
                    }

                    $status = $service->castVote($type, $voterId, $candidateId, ['device' => 'race', 'browser' => 'race'])->status;
                } catch (Throwable $e) {
                    $status = 'exception: ' . $e->getMessage();
                }

                file_put_contents($dir . DIRECTORY_SEPARATOR . $index, $status);
                // SIGKILL: tanpa shutdown PHP/PHPUnit agar koneksi & output induk tidak terganggu.
                posix_kill(getmypid(), SIGKILL);
            }

            $pids[] = $pid;
        }

        foreach ($pids as $pid) {
            pcntl_waitpid($pid, $status);
        }

        $results = [];
        foreach (array_keys($jobs) as $index) {
            $file      = $dir . DIRECTORY_SEPARATOR . $index;
            $results[] = is_file($file) ? (string) file_get_contents($file) : 'missing';
            @unlink($file);
        }
        rmdir($dir);

        return $results;
    }
}
