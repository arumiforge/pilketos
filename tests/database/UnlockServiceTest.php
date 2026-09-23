<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\UnlockResult;
use App\Services\UnlockService;
use App\Services\VoterType;
use App\Services\VoteResult;
use App\Services\VoteService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use Config\Database;

/**
 * Stage 3: unlock hak suara (transaction, riwayat, log, audit, kunci).
 *
 * @internal
 */
final class UnlockServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    private const REASON = 'Pemilih salah menekan pasangan, dikonfirmasi wali kelas.';

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function unlocker(): UnlockService
    {
        return new UnlockService($this->db);
    }

    private function castVote(VoterType $type, int $voterId, int $candidateId): int
    {
        $result = (new VoteService($this->db))->castVote($type, $voterId, $candidateId, ['device' => 'HP / Android', 'browser' => 'Chrome 126']);
        $this->assertTrue($result->isOk());

        return (int) $result->vote['id'];
    }

    public function testUnlockKeepsVoteAsHistoryAndLogsEverything(): void
    {
        Time::setTestNow(Time::now()->toDateTimeString());
        $voteId = $this->castVote(VoterType::Student, 1, 2);

        $result = $this->unlocker()->unlock(VoterType::Student, 1, $voteId, 1, '  ' . self::REASON . "\r\n", null, '10.0.0.5');

        $this->assertTrue($result->isOk(), $result->status);
        $this->assertSame(0, $this->db->transDepth);

        // Baris lama tidak dihapus dan pilihannya tidak diubah.
        $row = $this->db->table('student_votes')->where('id', $voteId)->get()->getRowArray();
        $this->assertSame('UNLOCKED', $row['status']);
        $this->assertSame('2', (string) $row['candidate_id']);
        $this->assertNull($row['active_lock']);
        $this->assertSame(Time::now()->toDateTimeString(), $row['unlocked_at']);
        $this->assertSame(0, $this->db->table('student_votes')->where('status', 'LOCKED')->countAllResults());

        $log = $this->db->table('vote_unlock_logs')->get()->getRowArray();
        $this->assertSame('1', (string) $log['student_id']);
        $this->assertSame((string) $voteId, (string) $log['student_vote_id']);
        $this->assertNull($log['teacher_id']);
        $this->assertSame('1', (string) $log['admin_id']);
        $this->assertSame(self::REASON, $log['reason']);

        $audit = $this->db->table('audit_logs')->get()->getRowArray();
        $this->assertSame('UNLOCK_VOTE', $audit['action']);
        $this->assertSame('1', (string) $audit['election_id']);
        $this->assertSame((string) $log['id'], (string) $audit['vote_unlock_log_id']);
        $this->assertSame('10.0.0.5', $audit['ip_address']);
        $this->assertStringContainsString('Ahmad Fauzan', $audit['description']);
        $this->assertStringContainsString('NISN 0000000001', $audit['description']);
        $this->assertStringNotContainsString('Bagas', $audit['description']);
    }

    public function testVoterMustVoteAgainThemselvesAfterUnlock(): void
    {
        $first = $this->castVote(VoterType::Teacher, 2, 1);

        $this->assertTrue($this->unlocker()->unlock(VoterType::Teacher, 2, $first, 1, self::REASON)->isOk());

        $state = (new VoteService($this->db))->ballotState(VoterType::Teacher, 2);
        $this->assertNull($state['vote']);
        $this->assertTrue($state['canVote']);

        $second = $this->castVote(VoterType::Teacher, 2, 3);

        $this->assertNotSame($first, $second);
        $this->assertSame(2, $this->db->table('teacher_votes')->where('teacher_id', 2)->countAllResults());
        $this->seeInDatabase('teacher_votes', ['id' => $second, 'status' => 'LOCKED', 'candidate_id' => 3]);
        $this->seeInDatabase('teacher_votes', ['id' => $first, 'status' => 'UNLOCKED', 'candidate_id' => 1]);
    }

    public function testReasonIsRequired(): void
    {
        $voteId = $this->castVote(VoterType::Student, 1, 1);

        foreach (['', '   ', 'pendek', str_repeat('a', 501), null, ['x']] as $reason) {
            $this->assertSame(UnlockResult::INVALID_REASON, $this->unlocker()->unlock(VoterType::Student, 1, $voteId, 1, $reason)->status);
        }

        $this->seeInDatabase('student_votes', ['id' => $voteId, 'status' => 'LOCKED']);
        $this->assertSame(0, $this->db->table('vote_unlock_logs')->countAllResults());
    }

    public function testStaleOrForeignVoteIdIsRejected(): void
    {
        $studentVote = $this->castVote(VoterType::Student, 1, 1);
        $teacherVote = $this->castVote(VoterType::Teacher, 1, 2);

        // Id suara milik pemilih lain / jenis lain / tidak ada.
        $this->assertSame(UnlockResult::VOTE_NOT_ACTIVE, $this->unlocker()->unlock(VoterType::Student, 2, $studentVote, 1, self::REASON)->status);
        $this->assertSame(UnlockResult::VOTE_NOT_ACTIVE, $this->unlocker()->unlock(VoterType::Student, 1, $teacherVote + 100, 1, self::REASON)->status);
        $this->assertSame(UnlockResult::VOTER_NOT_FOUND, $this->unlocker()->unlock(VoterType::Teacher, 99, $teacherVote, 1, self::REASON)->status);

        // Unlock kedua pada suara yang sama (klik ganda / dua admin) ditolak.
        $this->assertTrue($this->unlocker()->unlock(VoterType::Student, 1, $studentVote, 1, self::REASON)->isOk());
        $this->assertSame(UnlockResult::VOTE_NOT_ACTIVE, $this->unlocker()->unlock(VoterType::Student, 1, $studentVote, 1, self::REASON)->status);

        $this->assertSame(1, $this->db->table('vote_unlock_logs')->countAllResults());
        $this->seeInDatabase('teacher_votes', ['id' => $teacherVote, 'status' => 'LOCKED']);
        $this->assertSame(0, $this->db->transDepth);
        $this->assertTrue($this->db->transStatus());
    }

    public function testUnlockOnlyWhileElectionIsOngoing(): void
    {
        $voteId = $this->castVote(VoterType::Student, 3, 1);
        $this->db->table('elections')->where('id', 1)->update(['start_at' => '2026-10-01 07:00:00', 'end_at' => '2026-10-01 12:00:00']);

        $finished = $this->unlocker()->unlock(VoterType::Student, 3, $voteId, 1, self::REASON, Time::parse('2026-10-01 12:00:00'));
        $this->assertSame(UnlockResult::ELECTION_NOT_ONGOING, $finished->status);
        $this->assertSame('FINISHED', $finished->electionStatus);
        $this->assertStringContainsString('hasil akhir', $finished->message());

        $upcoming = $this->unlocker()->unlock(VoterType::Student, 3, $voteId, 1, self::REASON, Time::parse('2026-10-01 06:59:59'));
        $this->assertSame(UnlockResult::ELECTION_NOT_ONGOING, $upcoming->status);

        $this->seeInDatabase('student_votes', ['id' => $voteId, 'status' => 'LOCKED']);
        $this->assertTrue($this->unlocker()->unlock(VoterType::Student, 3, $voteId, 1, self::REASON, Time::parse('2026-10-01 11:59:59'))->isOk());
    }

    /**
     * Baris pemilih sedang dikunci transaction lain (mis. castVote yang belum
     * selesai): hasilnya RETRY, tanpa perubahan data dan koneksi tetap sehat.
     */
    public function testLockWaitTimeoutIsReportedAsRetry(): void
    {
        $voteId = $this->castVote(VoterType::Student, 1, 1);

        $other = Database::connect('tests', false);
        $other->transBegin();
        $other->query('SELECT id FROM students WHERE id = 1 FOR UPDATE');
        $this->db->query('SET SESSION innodb_lock_wait_timeout = 1');

        try {
            $result = $this->unlocker()->unlock(VoterType::Student, 1, $voteId, 1, self::REASON);
        } finally {
            $this->db->query('SET SESSION innodb_lock_wait_timeout = 50');
            $other->transRollback();
            $other->close();
        }

        $this->assertSame(UnlockResult::RETRY, $result->status);
        $this->seeInDatabase('student_votes', ['id' => $voteId, 'status' => 'LOCKED']);
        $this->assertSame(0, $this->db->table('audit_logs')->countAllResults());
        $this->assertTrue($this->unlocker()->unlock(VoterType::Student, 1, $voteId, 1, self::REASON)->isOk());
    }

    /**
     * Unlock dan suara ulang pemilih yang sama dijalankan bersamaan dari
     * proses terpisah: kunci baris pemilih membuat keduanya bergantian, dan
     * database tetap berisi tepat satu suara LOCKED.
     */
    public function testParallelUnlockAndRevoteKeepExactlyOneLockedVote(): void
    {
        if (! function_exists('pcntl_fork') || ! function_exists('posix_kill')) {
            $this->markTestSkipped('Ekstensi pcntl/posix tidak tersedia (mis. Windows/Laragon).');
        }

        $voteId = $this->castVote(VoterType::Student, 5, 1);
        $dir    = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'unlock-race-' . bin2hex(random_bytes(4));
        mkdir($dir);
        $startAt = microtime(true) + 0.8;
        $jobs    = ['unlock', 'unlock', 'vote', 'vote'];
        $pids    = [];

        foreach ($jobs as $index => $job) {
            $pid = pcntl_fork();

            if ($pid === 0) {
                $status = 'error';

                try {
                    $db = Database::connect('tests', false);

                    while (microtime(true) < $startAt) {
                        usleep(500);
                    }

                    $status = $job === 'unlock'
                        ? 'unlock:' . (new UnlockService($db))->unlock(VoterType::Student, 5, $voteId, 1, self::REASON)->status
                        : 'vote:' . (new VoteService($db))->castVote(VoterType::Student, 5, 2)->status;
                } catch (Throwable $e) {
                    $status = 'exception: ' . $e->getMessage();
                }

                file_put_contents($dir . DIRECTORY_SEPARATOR . $index, $status);
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

        $summary = implode(', ', $results);
        $this->assertSame(1, count(array_keys($results, 'unlock:' . UnlockResult::OK, true)), $summary);
        $this->assertSame(1, $this->db->table('vote_unlock_logs')->countAllResults(), $summary);
        $this->assertLessThanOrEqual(1, $this->db->table('student_votes')->where('student_id', 5)->where('status', 'LOCKED')->countAllResults(), $summary);
        $this->assertNotContains('missing', $results, $summary);
        foreach ($results as $result) {
            $this->assertStringNotContainsString('exception', $result);
            $this->assertStringNotContainsString(VoteResult::RETRY, $result);
        }
    }
}
