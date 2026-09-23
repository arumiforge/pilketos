<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\Grade;
use App\Models\ElectionModel;
use App\Services\AnalyticsService;
use App\Services\FinalResult;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Stage 4: verifikasi analitik (04-FINAL... bagian 9).
 *
 * Data acak tetapi deterministik (seed tetap): ratusan pemilih lintas kelas &
 * jenjang (termasuk Romawi dan kelas "Lainnya"), pemilih nonaktif, riwayat
 * UNLOCKED, suara pemilih nonaktif, dan pasangan yang dinonaktifkan setelah
 * menerima suara. Semua angka AnalyticsService dibandingkan dengan query SQL
 * INDEPENDEN (bukan kode yang sama), lalu dicek invariannya:
 *
 *   student voted + student not voted = total active students
 *   teacher voted + teacher not voted = total active teachers
 *   active student votes + active teacher votes = total election votes
 *
 * (aktif = status_aktif 1, suara sah = status LOCKED). Rekap kelas, jenjang,
 * jenis kelamin, jenis pemilih, per pasangan, detail suara, live count, dan
 * hasil akhir harus berasal dari angka yang sama.
 *
 * @internal
 */
final class AnalyticsConsistencyTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    private const CLASSES = ['7A', '7B', '7 c', 'VII-D', '8A', '8B', 'VIII C', '9A', '9B', 'IX-C', '10A'];

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function seedRandomElection(): void
    {
        mt_srand(20261001);
        $now = '2026-09-24 08:00:00';

        $students = [];
        for ($i = 1; $i <= 240; $i++) {
            $students[] = [
                'nisn'          => sprintf('1%09d', $i),
                'name'          => 'Siswa Uji ' . $i,
                'jenis_kelamin' => mt_rand(0, 1) === 0 ? 'L' : 'P',
                'kelas'         => self::CLASSES[mt_rand(0, count(self::CLASSES) - 1)],
                'nomor_absen'   => mt_rand(1, 32),
                'kodeunik'      => '01012013',
                'status_aktif'  => mt_rand(1, 10) === 1 ? 0 : 1,
                'created_at'    => $now,
                'updated_at'    => $now,
            ];
        }
        $this->db->table('students')->insertBatch($students);

        $teachers = [];
        for ($i = 1; $i <= 30; $i++) {
            $teachers[] = [
                'nip'          => sprintf('19800101%010d', $i),
                'name'         => 'Guru Uji ' . $i,
                'kodeunik'     => '01011980',
                'status_aktif' => mt_rand(1, 8) === 1 ? 0 : 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];
        }
        $this->db->table('teachers')->insertBatch($teachers);

        foreach (['student' => 'students', 'teacher' => 'teachers'] as $type => $table) {
            $rows = [];

            foreach ($this->db->table($table)->select('id')->get()->getResultArray() as $voter) {
                if (mt_rand(1, 100) <= 15) {
                    // Riwayat: suara lama yang dibuka admin (tidak dihitung).
                    $rows[] = $this->voteRow($type, (int) $voter['id'], mt_rand(1, 3), 'UNLOCKED');
                }

                if (mt_rand(1, 100) <= 72) {
                    $rows[] = $this->voteRow($type, (int) $voter['id'], mt_rand(1, 3), 'LOCKED');
                }
            }

            $this->db->table($type . '_votes')->insertBatch($rows);
        }

        // Pasangan 03 dinonaktifkan setelah menerima suara: tetap dihitung.
        $this->db->table('candidates')->where('id', 3)->update(['status_aktif' => 0]);
    }

    private function voteRow(string $type, int $voterId, int $candidateId, string $status): array
    {
        return [
            'election_id'  => 1,
            $type . '_id'  => $voterId,
            'candidate_id' => $candidateId,
            'status'       => $status,
            'voted_at'     => '2026-09-24 09:00:00',
            'unlocked_at'  => $status === 'UNLOCKED' ? '2026-09-24 09:30:00' : null,
        ];
    }

    private function scalar(string $sql): int
    {
        return (int) array_values($this->db->query($sql)->getRowArray())[0];
    }

    /**
     * @return array<int, int> candidate_id => suara sah (query independen)
     */
    private function sqlVotes(string $type, string $extraWhere = ''): array
    {
        $table = $type . 's';
        $rows  = $this->db->query(
            "SELECT v.candidate_id, COUNT(*) AS n FROM {$type}_votes v JOIN {$table} p ON p.id = v.{$type}_id
             WHERE v.election_id = 1 AND v.status = 'LOCKED' AND p.status_aktif = 1 {$extraWhere} GROUP BY v.candidate_id",
        )->getResultArray();

        $votes = [1 => 0, 2 => 0, 3 => 0];
        foreach ($rows as $row) {
            $votes[(int) $row['candidate_id']] = (int) $row['n'];
        }

        return $votes;
    }

    public function testEveryBreakdownMatchesIndependentSqlAndInvariantsHold(): void
    {
        $this->seedRandomElection();

        $election = model(ElectionModel::class)->getCurrentElection();
        $snapshot = service('analytics')->snapshot($election);
        $summary  = $snapshot['summary'];

        // --- total pemilih aktif (query independen)
        $activeStudents = $this->scalar('SELECT COUNT(*) FROM students WHERE status_aktif = 1');
        $activeTeachers = $this->scalar('SELECT COUNT(*) FROM teachers WHERE status_aktif = 1');
        $this->assertSame($activeStudents, $summary['students']['total']);
        $this->assertSame($activeTeachers, $summary['teachers']['total']);

        // --- invarian 04-FINAL bagian 9
        $this->assertSame($activeStudents, $summary['students']['voted'] + $summary['students']['not_voted']);
        $this->assertSame($activeTeachers, $summary['teachers']['voted'] + $summary['teachers']['not_voted']);
        $this->assertSame($summary['all']['voted'], $summary['students']['voted'] + $summary['teachers']['voted']);
        $this->assertSame($summary['all']['total'], $activeStudents + $activeTeachers);

        // --- suara per pasangan = query independen (LOCKED & pemilih aktif)
        $studentVotes = $this->sqlVotes('student');
        $teacherVotes = $this->sqlVotes('teacher');
        $this->assertSame($studentVotes, $summary['students']['votes']);
        $this->assertSame($teacherVotes, $summary['teachers']['votes']);
        $this->assertSame(array_sum($studentVotes), $summary['students']['voted']);
        $this->assertSame(array_sum($teacherVotes), $summary['teachers']['voted']);

        // Riwayat UNLOCKED & suara pemilih nonaktif memang ada di data, tetapi tidak dihitung.
        $this->assertGreaterThan(0, $this->scalar("SELECT COUNT(*) FROM student_votes WHERE status = 'UNLOCKED'"));
        $this->assertGreaterThan(0, $this->scalar("SELECT COUNT(*) FROM student_votes v JOIN students s ON s.id = v.student_id WHERE s.status_aktif = 0 AND v.status = 'LOCKED'"));
        $this->assertGreaterThan($summary['all']['voted'], $this->scalar('SELECT COUNT(*) FROM student_votes') + $this->scalar('SELECT COUNT(*) FROM teacher_votes'));

        $byCandidate = array_column($snapshot['candidates'], 'votes', 'id');
        foreach ([1, 2, 3] as $id) {
            $this->assertSame($studentVotes[$id] + $teacherVotes[$id], $byCandidate[$id]);
        }
        $this->assertSame($summary['all']['voted'], array_sum($byCandidate));
        $this->assertEqualsWithDelta(100.0, array_sum(array_column($snapshot['candidates'], 'percent')), 0.05);

        // Pasangan 03 nonaktif tetapi punya suara: tetap tampil.
        $this->assertContains(3, array_column($snapshot['candidates'], 'id'));

        // --- rekap kelas / jenjang / jenis kelamin / jenis pemilih = total yang sama
        foreach (['class', 'grade', 'gender'] as $group) {
            $groups = $snapshot['groups'][$group];
            $this->assertSame($activeStudents, array_sum(array_column($groups, 'total')), $group);
            $this->assertSame($summary['students']['voted'], array_sum(array_column($groups, 'voted')), $group);
            $this->assertSame($summary['students']['not_voted'], array_sum(array_column($groups, 'not_voted')), $group);

            foreach ([1, 2, 3] as $id) {
                $this->assertSame($studentVotes[$id], array_sum(array_map(static fn (array $g): int => $g['votes'][$id], $groups)), "{$group} #{$id}");
            }
        }

        $this->assertSame([$summary['students']['total'], $summary['teachers']['total']], array_column($snapshot['groups']['type'], 'total'));

        // Kelas dibakukan ("7 c" = "7 C"), jenjang Romawi dikenali, "10A" masuk Lainnya.
        $classKeys = array_column($snapshot['groups']['class'], 'key');
        $this->assertContains('7 C', $classKeys);
        $this->assertSame(['7', '8', '9', 'lainnya'], array_column($snapshot['groups']['grade'], 'key'));

        // Per kelas = query independen per kelas bentuk baku.
        foreach ($snapshot['groups']['class'] as $group) {
            $total = $this->db->table('students')->where('status_aktif', 1)->get()->getResultArray();
            $inClass = array_filter($total, static fn (array $s): bool => Grade::normalizeKelas($s['kelas']) === $group['key']);
            $this->assertSame(count($inClass), $group['total'], $group['key']);
        }

        // --- per jenis kelamin = query independen
        foreach ($snapshot['groups']['gender'] as $group) {
            $this->assertSame($this->sqlVotes('student', "AND p.jenis_kelamin = '{$group['key']}'"), $group['votes'], $group['key']);
        }

        // --- detail suara (filter bawaan) = total suara sah
        $detail = service('analytics')->detailVotes($election, AnalyticsService::detailFilters([], [1, 2, 3], []), 1, 25);
        $this->assertSame($summary['all']['voted'], $detail['total']);

        // --- live count JSON = snapshot yang sama
        $encoded = json_encode(AnalyticsService::toJson($snapshot));
        $json    = json_decode($encoded, true);
        $this->assertSame($summary['all']['voted'], $json['summary']['all']['voted']);
        $this->assertSame(array_values($studentVotes), array_values($json['summary']['students']['votes']));
        // Map suara per pasangan tetap objek {"<id>": n} di JSON (bukan array).
        $this->assertStringContainsString(sprintf('"votes":{"1":%d,"2":%d,"3":%d}', ...array_values($studentVotes)), $encoded);
    }

    public function testFinalResultUsesTheSameNumbers(): void
    {
        $this->seedRandomElection();
        $this->db->table('elections')->where('id', 1)->update(['start_at' => '2026-09-24 07:00:00', 'end_at' => '2026-09-24 12:00:00']);
        Time::setTestNow('2026-09-24 12:00:00');

        $election = model(ElectionModel::class)->getCurrentElection();
        $this->assertSame('FINISHED', $election['status']);

        $snapshot = service('analytics')->snapshot($election);
        $final    = FinalResult::build($election, $snapshot);

        $this->assertTrue($final['available']);
        $this->assertSame($snapshot['summary']['all']['voted'], $final['total_votes']);
        $this->assertSame($final['total_votes'], array_sum(array_column($final['ranking'], 'votes')));

        $votes = array_column($final['ranking'], 'votes');
        $sorted = $votes;
        rsort($sorted);
        $this->assertSame($sorted, $votes);

        if ($final['state'] === FinalResult::WINNER) {
            $this->assertSame($votes[0], $final['winner']['votes']);
            $this->assertSame($votes[0] - $votes[1], $final['margin']);
        } else {
            $this->assertSame(FinalResult::TIE, $final['state']);
        }
    }
}
