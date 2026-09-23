<?php

use App\Database\Migrations\AddVoteIntegrityGuards;
use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\SystemCheck;
use App\Services\UnlockService;
use App\Services\VoterType;
use App\Services\VoteResult;
use App\Services\VoteService;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Stage 4: integritas suara (04-FINAL... bagian 3).
 *
 * State per pemilih per election:
 *   NO_ACTIVE_VOTE -> LOCKED -> (unlock) NO_ACTIVE_VOTE, baris lama UNLOCKED
 *   -> (vote lagi) LOCKED dengan baris BARU.
 *
 * Selain lewat service, database sendiri (migration 2026-04-01-000001)
 * menolak: menghapus suara/log, mengubah pilihan/pemilih/waktu, mengunci
 * ulang riwayat UNLOCKED, state status/unlocked_at yang tidak konsisten, dan
 * mengubah/menghapus audit log. Satu suara aktif tetap dijamin unique key.
 *
 * @internal
 */
final class VoteIntegrityTest extends CIUnitTestCase
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

    private function cast(VoterType $type, int $voterId, int $candidateId): int
    {
        $result = (new VoteService($this->db))->castVote($type, $voterId, $candidateId, ['device' => 'HP / Android', 'browser' => 'Chrome 126']);
        $this->assertTrue($result->isOk(), $result->status);

        return (int) $result->vote['id'];
    }

    /**
     * Jalankan query yang HARUS ditolak database; kembalikan kode error MySQL.
     */
    private function rejected(callable $query): int
    {
        try {
            $query();
        } catch (DatabaseException $e) {
            return (int) $e->getCode();
        }

        $this->fail('Database seharusnya menolak query ini.');
    }

    // ------------------------------------------------------------------
    // transisi state lewat service
    // ------------------------------------------------------------------

    public function testStateMachineThroughServicesKeepsAuditableHistory(): void
    {
        $votes = VoterType::Student->voteModel();

        // NO_ACTIVE_VOTE
        $this->assertNull($votes->findActiveVote(1, 1));

        // -> LOCKED
        $first = $this->cast(VoterType::Student, 1, 2);
        $this->assertSame((string) $first, (string) $votes->findActiveVote(1, 1)['id']);
        $this->assertSame(VoteResult::ALREADY_VOTED, (new VoteService($this->db))->castVote(VoterType::Student, 1, 3)->status);

        // -> NO_ACTIVE_VOTE (baris lama UNLOCKED, tidak dihapus)
        $unlock = (new UnlockService($this->db))->unlock(VoterType::Student, 1, $first, 1, self::REASON);
        $this->assertTrue($unlock->isOk(), $unlock->status);
        $this->assertNull($votes->findActiveVote(1, 1));
        $this->seeInDatabase('student_votes', ['id' => $first, 'status' => 'UNLOCKED', 'candidate_id' => 2]);

        // -> LOCKED dengan baris baru (pemilih memilih sendiri)
        $second = $this->cast(VoterType::Student, 1, 3);
        $this->assertNotSame($first, $second);

        $rows = $this->db->table('student_votes')->where('student_id', 1)->orderBy('id')->get()->getResultArray();
        $this->assertSame([['UNLOCKED', '2'], ['LOCKED', '3']], array_map(static fn (array $r): array => [$r['status'], (string) $r['candidate_id']], $rows));
        $this->assertSame(1, $this->db->table('student_votes')->where('student_id', 1)->where('status', 'LOCKED')->countAllResults());

        // Riwayat dapat diaudit: log unlock menunjuk baris lama, audit mencatat tindakan.
        $this->seeInDatabase('vote_unlock_logs', ['student_id' => 1, 'student_vote_id' => $first, 'admin_id' => 1]);
        $this->seeInDatabase('audit_logs', ['action' => 'UNLOCK_VOTE', 'admin_id' => 1]);
    }

    // ------------------------------------------------------------------
    // penjaga di tingkat database
    // ------------------------------------------------------------------

    public function testVoteRowsCannotBeDeleted(): void
    {
        $student = $this->cast(VoterType::Student, 1, 1);
        $teacher = $this->cast(VoterType::Teacher, 1, 1);

        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('student_votes')->where('id', $student)->delete()));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('teacher_votes')->where('id', $teacher)->delete()));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->query('DELETE FROM student_votes')));

        $this->assertSame(1, $this->db->table('student_votes')->countAllResults());
        $this->assertSame(1, $this->db->table('teacher_votes')->countAllResults());
    }

    public function testVoteChoiceVoterElectionTimeAndDeviceCannotBeChanged(): void
    {
        $id = $this->cast(VoterType::Student, 1, 1);

        foreach ([
            ['candidate_id' => 2],
            ['student_id' => 2],
            ['voted_at' => '2020-01-01 00:00:00'],
            ['device_info' => 'Palsu'],
            ['browser_info' => null],
        ] as $change) {
            $this->assertSame(1644, $this->rejected(fn () => $this->db->table('student_votes')->where('id', $id)->update($change)), json_encode($change));
        }

        $teacherVote = $this->cast(VoterType::Teacher, 2, 3);
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('teacher_votes')->where('id', $teacherVote)->update(['candidate_id' => 1])));

        $this->seeInDatabase('student_votes', ['id' => $id, 'candidate_id' => 1, 'student_id' => 1, 'device_info' => 'HP / Android']);
        $this->seeInDatabase('teacher_votes', ['id' => $teacherVote, 'candidate_id' => 3]);
    }

    public function testUnlockedHistoryIsFinalAndStatesStayConsistent(): void
    {
        $id  = $this->cast(VoterType::Student, 1, 1);
        $now = Time::now()->toDateTimeString();

        // CHECK: UNLOCKED wajib punya unlocked_at, LOCKED tidak boleh punya.
        $this->assertContains($this->rejected(fn () => $this->db->table('student_votes')->where('id', $id)->update(['status' => 'UNLOCKED'])), [3819, 4025]);
        $this->assertContains($this->rejected(fn () => $this->db->table('student_votes')->where('id', $id)->update(['unlocked_at' => $now])), [3819, 4025]);

        // Satu-satunya transisi sah.
        $this->db->table('student_votes')->where('id', $id)->update(['status' => 'UNLOCKED', 'unlocked_at' => $now, 'updated_at' => $now]);
        $this->seeInDatabase('student_votes', ['id' => $id, 'status' => 'UNLOCKED']);

        // Riwayat tidak dapat dikunci ulang atau diubah waktunya.
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('student_votes')->where('id', $id)->update(['status' => 'LOCKED', 'unlocked_at' => null])));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('student_votes')->where('id', $id)->update(['unlocked_at' => '2030-01-01 00:00:00'])));

        // Insert langsung dengan state tidak konsisten juga ditolak.
        $this->assertContains($this->rejected(fn () => $this->db->table('student_votes')->insert([
            'election_id' => 1, 'student_id' => 2, 'candidate_id' => 1, 'status' => 'UNLOCKED', 'voted_at' => $now,
        ])), [3819, 4025]);
    }

    public function testDatabaseStillAllowsOnlyOneActiveVote(): void
    {
        $this->cast(VoterType::Teacher, 1, 2);

        $code = $this->rejected(fn () => $this->db->table('teacher_votes')->insert([
            'election_id' => 1, 'teacher_id' => 1, 'candidate_id' => 3, 'status' => 'LOCKED', 'voted_at' => Time::now()->toDateTimeString(),
        ]));

        $this->assertContains($code, [1062, 1586]);
        $this->assertSame(1, $this->db->table('teacher_votes')->where('teacher_id', 1)->countAllResults());
    }

    public function testUnlockLogAndAuditLogAreAppendOnly(): void
    {
        $id     = $this->cast(VoterType::Teacher, 1, 2);
        $result = (new UnlockService($this->db))->unlock(VoterType::Teacher, 1, $id, 1, self::REASON);
        $this->assertTrue($result->isOk());

        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('vote_unlock_logs')->update(['reason' => 'diubah diam-diam'])));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('vote_unlock_logs')->where('id', $result->unlock['log_id'])->delete()));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('audit_logs')->update(['description' => 'dihapus jejaknya'])));
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('audit_logs')->where('action', 'UNLOCK_VOTE')->delete()));

        $this->seeInDatabase('vote_unlock_logs', ['id' => $result->unlock['log_id'], 'reason' => self::REASON]);
        $this->assertSame(1, $this->db->table('audit_logs')->where('action', 'UNLOCK_VOTE')->countAllResults());
    }

    public function testIntegrityMigrationCanBeRerunAfterPartialFailure(): void
    {
        require_once APPPATH . 'Database/Migrations/2026-04-01-000001_AddVoteIntegrityGuards.php';

        // Keadaan setelah up() gagal di tengah (mis. error 1419 di MySQL):
        // CHECK sudah terpasang, sebagian trigger belum dibuat.
        $this->db->query('DROP TRIGGER trg_teacher_votes_guard_delete');
        $this->db->query('DROP TRIGGER trg_audit_logs_guard_update');

        (new AddVoteIntegrityGuards())->up();
        (new AddVoteIntegrityGuards())->up();

        $triggers = array_column($this->db->query('SELECT TRIGGER_NAME FROM information_schema.TRIGGERS WHERE TRIGGER_SCHEMA = DATABASE()')->getResultArray(), 'TRIGGER_NAME');
        $expected = SystemCheck::TRIGGERS;
        sort($triggers);
        sort($expected);
        $this->assertSame($expected, $triggers);

        $checks = $this->db->query(
            "SELECT COUNT(*) AS n FROM information_schema.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE() AND CONSTRAINT_NAME IN ('chk_student_votes_state', 'chk_teacher_votes_state')",
        )->getRowArray();
        $this->assertSame(2, (int) $checks['n']);

        // Penjaga kembali bekerja.
        $id = $this->cast(VoterType::Teacher, 1, 1);
        $this->assertSame(1644, $this->rejected(fn () => $this->db->table('teacher_votes')->where('id', $id)->delete()));
    }

    public function testGuardsDoNotBlockNormalAdminWork(): void
    {
        // Audit baru tetap dapat ditambahkan; data pemilih/kandidat tetap dapat dikelola.
        model(\App\Models\AuditLogModel::class)->log(1, 'SCHEDULE_UPDATE', 'Uji tambah audit.');
        $this->db->table('students')->where('id', 3)->update(['name' => 'Candra S.']);
        $this->db->table('candidates')->where('id', 1)->update(['visi' => 'Visi baru.']);

        $this->seeInDatabase('audit_logs', ['description' => 'Uji tambah audit.']);
        $this->seeInDatabase('students', ['id' => 3, 'name' => 'Candra S.']);
    }
}
