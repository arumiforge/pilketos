<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\ElectionModel;
use App\Services\AnalyticsService;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;

/**
 * Stage 3: analitik admin langsung terhadap MySQL/MariaDB.
 *
 * Data seed: 11 siswa aktif (7A:2, 7B:2, 8A:2, 8B:2, 9A:2, 9B:1 aktif +
 * 1 nonaktif) dan 4 guru aktif (+1 nonaktif), 3 pasangan calon.
 *
 * @internal
 */
final class AnalyticsServiceTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    private function service(): AnalyticsService
    {
        return new AnalyticsService($this->db);
    }

    private function election(): array
    {
        return model(ElectionModel::class)->getCurrentElection();
    }

    private function vote(string $type, int $voterId, int $candidateId, string $status = 'LOCKED'): int
    {
        $this->db->table($type . '_votes')->insert([
            'election_id'  => 1,
            $type . '_id'  => $voterId,
            'candidate_id' => $candidateId,
            'status'       => $status,
            'voted_at'     => Time::now()->toDateTimeString(),
            'unlocked_at'  => $status === 'UNLOCKED' ? Time::now()->toDateTimeString() : null,
            'device_info'  => 'HP / Android',
            'browser_info' => 'Chrome 126',
        ]);

        return (int) $this->db->insertID();
    }

    /**
     * Skenario campuran: suara sah, riwayat UNLOCKED, dan suara pemilih nonaktif.
     */
    private function seedVotes(): void
    {
        $this->vote('student', 1, 1);   // 7A L
        $this->vote('student', 2, 2);   // 7A P
        $this->vote('student', 3, 1);   // 7B L
        $this->vote('student', 5, 3);   // 8A L
        $this->vote('student', 9, 2);   // 9A L
        $this->vote('student', 4, 1, 'UNLOCKED'); // riwayat, tidak dihitung
        $this->vote('teacher', 1, 1);
        $this->vote('teacher', 2, 3);

        // Suara pemilih yang kemudian dinonaktifkan tidak dihitung.
        $this->vote('student', 12, 3);
        $this->vote('teacher', 5, 2);
    }

    private function group(array $groups, string $key): array
    {
        foreach ($groups as $group) {
            if ((string) $group['key'] === $key) {
                return $group;
            }
        }

        $this->fail('Kelompok ' . $key . ' tidak ditemukan');
    }

    public function testOverallCountsOnlyLockedVotesOfActiveVoters(): void
    {
        $this->seedVotes();

        $snap = $this->service()->snapshot($this->election());

        $this->assertSame(11, $snap['summary']['students']['total']);
        $this->assertSame(5, $snap['summary']['students']['voted']);
        $this->assertSame(6, $snap['summary']['students']['not_voted']);
        $this->assertSame(4, $snap['summary']['teachers']['total']);
        $this->assertSame(2, $snap['summary']['teachers']['voted']);
        $this->assertSame(2, $snap['summary']['teachers']['not_voted']);
        $this->assertSame(15, $snap['summary']['all']['total']);
        $this->assertSame(7, $snap['summary']['all']['voted']);
        $this->assertSame(8, $snap['summary']['all']['not_voted']);
        $this->assertSame(46.67, $snap['summary']['all']['participation']);

        // Per kandidat: 01 = 3 (2 siswa + 1 guru), 02 = 2, 03 = 2.
        $this->assertSame([1 => 3, 2 => 2, 3 => 2], $snap['summary']['all']['votes']);
        $this->assertSame([1 => 42.86, 2 => 28.57, 3 => 28.57], $snap['summary']['all']['shares']);

        $byNumber = array_column($snap['candidates'], null, 'label');
        $this->assertSame(3, $byNumber['01']['votes']);
        $this->assertSame(2, $byNumber['01']['student_votes']);
        $this->assertSame(1, $byNumber['01']['teacher_votes']);
        $this->assertSame('#C4432B', $byNumber['01']['accent']);
    }

    /**
     * Invarian Stage 4 bagian 9 berlaku di semua kelompok.
     */
    public function testBreakdownsAreConsistentWithTotals(): void
    {
        $this->seedVotes();

        $snap    = $this->service()->snapshot($this->election());
        $summary = $snap['summary'];

        $this->assertSame($summary['students']['total'], $summary['students']['voted'] + $summary['students']['not_voted']);
        $this->assertSame($summary['teachers']['total'], $summary['teachers']['voted'] + $summary['teachers']['not_voted']);
        $this->assertSame($summary['all']['voted'], $summary['students']['voted'] + $summary['teachers']['voted']);
        $this->assertSame($summary['all']['voted'], array_sum(array_column($snap['candidates'], 'votes')));

        foreach (['gender', 'grade', 'class'] as $name) {
            $groups = $snap['groups'][$name];
            $this->assertSame($summary['students']['total'], array_sum(array_column($groups, 'total')), $name);
            $this->assertSame($summary['students']['voted'], array_sum(array_column($groups, 'voted')), $name);

            foreach ($groups as $group) {
                $this->assertSame($group['total'], $group['voted'] + $group['not_voted']);
                $this->assertSame($group['voted'], array_sum($group['votes']));
            }
        }
    }

    public function testVoterTypeBreakdown(): void
    {
        $this->seedVotes();

        $types = $this->service()->snapshot($this->election())['groups']['type'];

        $student = $this->group($types, 'student');
        $this->assertSame('Siswa', $student['label']);
        $this->assertSame([11, 5, 6], [$student['total'], $student['voted'], $student['not_voted']]);
        $this->assertSame([1 => 2, 2 => 2, 3 => 1], $student['votes']);

        $teacher = $this->group($types, 'teacher');
        $this->assertSame('Guru', $teacher['label']);
        $this->assertSame([4, 2, 2], [$teacher['total'], $teacher['voted'], $teacher['not_voted']]);
        $this->assertSame([1 => 1, 2 => 0, 3 => 1], $teacher['votes']);
        $this->assertSame(50.0, $teacher['shares'][1]);
    }

    public function testStudentGenderBreakdown(): void
    {
        $this->seedVotes();

        $gender = $this->service()->snapshot($this->election())['groups']['gender'];

        $male = $this->group($gender, 'L');
        $this->assertSame('Laki-laki', $male['label']);
        // L aktif: 1,3,5,7,9,11 = 6; memilih: 1(01), 3(01), 5(03), 9(02).
        $this->assertSame([6, 4, 2], [$male['total'], $male['voted'], $male['not_voted']]);
        $this->assertSame([1 => 2, 2 => 1, 3 => 1], $male['votes']);
        $this->assertSame([1 => 50.0, 2 => 25.0, 3 => 25.0], $male['shares']);

        $female = $this->group($gender, 'P');
        $this->assertSame('Perempuan', $female['label']);
        // P aktif: 2,4,6,8,10 = 5 (12 nonaktif); memilih: 2(02). Siswa 4 hanya riwayat UNLOCKED.
        $this->assertSame([5, 1, 4], [$female['total'], $female['voted'], $female['not_voted']]);
        $this->assertSame(20.0, $female['participation']);
    }

    public function testClassBreakdownIsSortedByGradeAndName(): void
    {
        $this->seedVotes();

        $classes = $this->service()->snapshot($this->election())['groups']['class'];

        $this->assertSame(['7A', '7B', '8A', '8B', '9A', '9B'], array_column($classes, 'label'));

        $sevenA = $this->group($classes, '7A');
        $this->assertSame([2, 2, 0], [$sevenA['total'], $sevenA['voted'], $sevenA['not_voted']]);
        $this->assertSame([1 => 1, 2 => 1, 3 => 0], $sevenA['votes']);

        // 9B: siswa 12 nonaktif tidak dihitung walau punya suara LOCKED.
        $nineB = $this->group($classes, '9B');
        $this->assertSame([1, 0, 1], [$nineB['total'], $nineB['voted'], $nineB['not_voted']]);
    }

    public function testGradeBreakdownIncludesRomanNumeralsAndOtherBucket(): void
    {
        $this->seedVotes();
        $this->db->table('students')->where('id', 7)->update(['kelas' => 'VIII-C']);
        $this->db->table('students')->where('id', 8)->update(['kelas' => 'x akselerasi']);

        $grades = $this->service()->snapshot($this->election())['groups']['grade'];

        $this->assertSame(['7', '8', '9', 'lainnya'], array_column($grades, 'key'));
        $this->assertSame(['Kelas 7', 'Kelas 8', 'Kelas 9', 'Lainnya'], array_column($grades, 'label'));

        $seven = $this->group($grades, '7');
        $this->assertSame([4, 3, 1], [$seven['total'], $seven['voted'], $seven['not_voted']]);
        $this->assertSame([1 => 2, 2 => 1, 3 => 0], $seven['votes']);
        $this->assertSame(75.0, $seven['participation']);

        $eight = $this->group($grades, '8');
        $this->assertSame([3, 1, 2], [$eight['total'], $eight['voted'], $eight['not_voted']]);

        $other = $this->group($grades, 'lainnya');
        $this->assertSame([1, 0, 1], [$other['total'], $other['voted'], $other['not_voted']]);
    }

    public function testNoElectionMeansEveryoneHasNotVoted(): void
    {
        $snap = $this->service()->snapshot(null);

        $this->assertNull($snap['election']);
        $this->assertSame(15, $snap['summary']['all']['total']);
        $this->assertSame(0, $snap['summary']['all']['voted']);
        $this->assertSame(0.0, $snap['summary']['all']['participation']);
        $this->assertCount(3, $snap['candidates']);
    }

    public function testInactiveCandidateIsHiddenOnlyWhenItHasNoVotes(): void
    {
        $this->db->table('candidates')->whereIn('id', [2, 3])->update(['status_aktif' => 0]);
        $this->vote('student', 1, 3);

        $labels = array_column($this->service()->snapshot($this->election())['candidates'], 'label');

        $this->assertSame(['01', '03'], $labels);
    }

    public function testJsonShapeKeepsCandidateMapsAsObjects(): void
    {
        $this->seedVotes();

        $json = json_decode(json_encode(AnalyticsService::toJson($this->service()->snapshot($this->election()))), true);

        $this->assertSame(['1' => 3, '2' => 2, '3' => 2], $json['summary']['all']['votes']);
        $this->assertSame(['1' => 1, '2' => 1, '3' => 0], $json['groups']['class'][0]['votes']);
        $this->assertStringContainsString('"votes":{"1":', json_encode(AnalyticsService::toJson($this->service()->snapshot($this->election()))));
    }

    public function testDetailVotesDefaultListsExactlyTheCountedVotes(): void
    {
        $this->seedVotes();
        $service  = $this->service();
        $election = $this->election();
        $filters  = AnalyticsService::detailFilters([], [1, 2, 3], $service->classes());

        $result = $service->detailVotes($election, $filters, 1, 50);

        $this->assertSame(7, $result['total']);
        $this->assertCount(7, $result['rows']);
        $this->assertSame(['LOCKED'], array_values(array_unique(array_column($result['rows'], 'status'))));
        $this->assertSame(['1'], array_values(array_unique(array_map('strval', array_column($result['rows'], 'voter_active')))));

        $types = array_count_values(array_column($result['rows'], 'voter_type'));
        $this->assertSame(['student' => 5, 'teacher' => 2], ['student' => $types['student'], 'teacher' => $types['teacher']]);

        $first = array_values(array_filter($result['rows'], static fn ($r) => $r['voter_type'] === 'student' && (int) $r['voter_id'] === 1))[0];
        $this->assertSame('Ahmad Fauzan', $first['name']);
        $this->assertSame('0000000001', $first['identifier']);
        $this->assertSame('7A', $first['kelas']);
        $this->assertSame('L', $first['jenis_kelamin']);
        $this->assertSame('Arka Wibisana', $first['nama_ketua']);
        $this->assertSame('HP / Android', $first['device_info']);
        $this->assertSame('Chrome 126', $first['browser_info']);
    }

    public function testDetailVotesFiltersAndPagination(): void
    {
        $this->seedVotes();
        $service  = $this->service();
        $election = $this->election();
        $classes  = $service->classes();
        $filter   = static fn (array $in) => AnalyticsService::detailFilters($in, [1, 2, 3], $classes);

        $this->assertSame(2, $service->detailVotes($election, $filter(['type' => 'teacher']), 1, 50)['total']);
        $this->assertSame(3, $service->detailVotes($election, $filter(['candidate' => '1']), 1, 50)['total']);
        $this->assertSame(2, $service->detailVotes($election, $filter(['kelas' => '7a']), 1, 50)['total']);
        $this->assertSame(4, $service->detailVotes($election, $filter(['gender' => 'l']), 1, 50)['total']);
        $this->assertSame(1, $service->detailVotes($election, $filter(['q' => 'Fauzan']), 1, 50)['total']);
        $this->assertSame(1, $service->detailVotes($election, $filter(['q' => '000000000000000002']), 1, 50)['total']);

        // Riwayat unlock dan "semua baris" (termasuk suara pemilih nonaktif).
        $this->assertSame(1, $service->detailVotes($election, $filter(['status' => 'UNLOCKED']), 1, 50)['total']);
        $this->assertSame(10, $service->detailVotes($election, $filter(['status' => 'all']), 1, 50)['total']);

        // Nilai asing diabaikan, bukan error.
        $unknown = $filter(['type' => 'admin', 'candidate' => '99', 'gender' => 'X', 'kelas' => '1Z', 'status' => 'DROP']);
        $this->assertSame(['q' => '', 'type' => '', 'rombel' => '', 'gender' => '', 'candidate' => 0, 'status' => 'LOCKED'], $unknown);

        $page1 = $service->detailVotes($election, $filter([]), 1, 3);
        $page3 = $service->detailVotes($election, $filter([]), 3, 3);
        $this->assertSame(7, $page1['total']);
        $this->assertCount(3, $page1['rows']);
        $this->assertCount(1, $page3['rows']);
    }

    public function testClassesAreNormalizedAndSorted(): void
    {
        $this->db->table('students')->where('id', 1)->update(['kelas' => '7a']);
        $this->db->table('students')->where('id', 11)->update(['kelas' => 'IX C']);

        $classes = $this->service()->classes();

        $this->assertSame(['7A', '7B', '8A', '8B', '9A', '9B', 'IX C'], $classes);
    }
}
