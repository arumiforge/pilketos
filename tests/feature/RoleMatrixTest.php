<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Commands\Utilities\Routes\FilterCollector;
use CodeIgniter\Commands\Utilities\Routes\SampleURIGenerator;
use CodeIgniter\I18n\Time;
use CodeIgniter\Router\DefinedRouteCollector;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * Stage 4: route matrix & role permission matrix (04-FINAL... bagian 2, 17, 18).
 *
 * 1. Setiap route yang terdaftar dicek filternya: di luar daftar publik yang
 *    eksplisit, route admin/student/teacher WAJIB memakai filter role-nya,
 *    sehingga route baru tidak mungkin lolos tanpa proteksi.
 * 2. Tabel kapabilitas (Student / Teacher / Admin) diuji lewat HTTP.
 *
 * @internal
 */
final class RoleMatrixTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    /**
     * Satu-satunya route tanpa filter autentikasi (route matrix "public").
     * live-count (redesign beranda): hanya persentase per pasangan + partisipasi.
     */
    private const PUBLIC_ROUTES = [
        'GET'  => ['/', 'election/clock', 'live-count', 'student/login', 'teacher/login', 'admin/login', 'student', 'teacher', 'admin'],
        'POST' => ['student/login', 'teacher/login', 'admin/login', 'student', 'teacher', 'admin'],
    ];

    private const STUDENT = ['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true];
    private const TEACHER = ['user_type' => 'teacher', 'teacher_id' => 1, 'isLoggedIn' => true];
    private const ADMIN   = ['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true];

    protected function setUp(): void
    {
        parent::setUp();

        Services::resetSingle('throttler');
        cache()->clean();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function json($result): array
    {
        return json_decode($result->getJSON(), true);
    }

    private function ajax(array $session)
    {
        return $this->withSession($session)
            ->withHeaders(['X-CSRF-TOKEN' => csrf_hash(), 'X-Requested-With' => 'XMLHttpRequest', 'Accept' => 'application/json'])
            ->withBodyFormat('json');
    }

    private function page(array $session, string $path)
    {
        return $this->withSession($session)->withHeaders([])->withBodyFormat('')->get($path);
    }

    private function postAs(array $session, string $path, array $data = [])
    {
        return $this->withSession($session)->withHeaders([])->withBodyFormat('')->post($path, [csrf_token() => csrf_hash()] + $data);
    }

    // ------------------------------------------------------------------
    // route matrix
    // ------------------------------------------------------------------

    public function testEveryRouteIsPublicByDesignOrProtectedByItsRoleFilter(): void
    {
        $collection = service('routes')->loadRoutes();
        $samples    = new SampleURIGenerator();
        $finder     = new FilterCollector();
        $seen       = ['GET' => [], 'POST' => []];

        foreach ((new DefinedRouteCollector($collection))->collect() as $route) {
            $verb = strtoupper($route['method']);
            $path = (string) $route['route'];

            if (! isset($seen[$verb])) {
                continue; // HEAD/PUT/... hanya muncul dari addRedirect()
            }

            $seen[$verb][] = $path;

            // Alamat pendek (student -> student/dashboard) hanya redirect ke route terproteksi.
            if ($collection->isRedirect($path)) {
                $this->assertContains($path, self::PUBLIC_ROUTES[$verb], "{$verb} {$path} redirect tidak terdokumentasi");
                $this->assertSame($path . '/dashboard', $route['handler'], "{$verb} {$path}");

                continue;
            }

            $before = $finder->get($verb, $samples->get($path))['before'];
            $auth   = array_values(array_intersect($before, ['adminauth', 'studentauth', 'teacherauth']));

            // CSRF & filter global selalu aktif untuk semua route.
            $this->assertContains('csrf', $before, "{$verb} {$path} tanpa CSRF");

            if (in_array($path, self::PUBLIC_ROUTES[$verb], true)) {
                $this->assertSame([], $auth, "{$verb} {$path} publik");

                continue;
            }

            $prefix = strtok($path, '/');
            $this->assertContains($prefix, ['admin', 'student', 'teacher'], "{$verb} {$path} tidak terdaftar di route matrix");
            $this->assertSame([$prefix . 'auth'], $auth, "{$verb} {$path} wajib memakai filter {$prefix}auth");
        }

        foreach (self::PUBLIC_ROUTES as $verb => $list) {
            foreach ($list as $path) {
                $this->assertContains($path, $seen[$verb], "{$verb} {$path} terdokumentasi tetapi tidak ada");
            }
        }

        $this->assertGreaterThan(60, count($seen['GET']) + count($seen['POST']));
    }

    public function testNoAdminRouteWritesVotes(): void
    {
        foreach (service('routes')->getRoutes('POST', false) as $route => $handler) {
            if (str_starts_with((string) $route, 'admin/')) {
                $this->assertStringNotContainsString('VoteController', (string) $handler, (string) $route);
            }
        }
    }

    // ------------------------------------------------------------------
    // role permission matrix
    // ------------------------------------------------------------------

    public function testLoginIsSeparatedPerRole(): void
    {
        $studentCred = ['nisn' => '0000000001', 'kodeunik' => '05062013'];
        $teacherCred = ['nip' => '000000000000000004', 'kodeunik' => '01061992'];

        // Login Student: Yes / No / No
        $this->postAs([], 'student/login', $studentCred)->assertRedirectTo(site_url('student/dashboard'));
        $this->postAs([], 'student/login', ['nisn' => '000000000000000004', 'kodeunik' => '01061992']);
        $this->assertNotSame('teacher', session('user_type'));
        $this->postAs([], 'student/login', ['nisn' => 'admin', 'kodeunik' => 'admin123']);
        $this->assertNotSame('admin', session('user_type'));

        // Login Teacher: No / Yes / No
        $this->postAs([], 'teacher/login', $teacherCred)->assertRedirectTo(site_url('teacher/dashboard'));
        $this->postAs([], 'teacher/login', ['nip' => '0000000001', 'kodeunik' => '05062013']);
        $this->assertNotSame('student', session('user_type'));

        // Admin Login: No / No / Yes
        $this->postAs([], 'admin/login', ['username' => 'admin', 'password' => 'admin123'])->assertRedirectTo(site_url('admin/dashboard'));
        $this->postAs([], 'admin/login', ['username' => '0000000001', 'password' => '05062013']);
        $this->assertNotSame('student', session('user_type'));
    }

    public function testStudentCannotEnterTeacherOrAdminAreas(): void
    {
        foreach (['teacher/dashboard', 'teacher/vote', 'teacher/my-vote'] as $path) {
            $this->page(self::STUDENT, $path)->assertRedirectTo(site_url('teacher/login'));
        }

        foreach (['admin/dashboard', 'admin/analytics', 'admin/analytics/votes', 'admin/results', 'admin/students', 'admin/audit'] as $path) {
            $this->page(self::STUDENT, $path)->assertRedirectTo(site_url('admin/login'));
        }

        $this->ajax(self::STUDENT)->get('admin/live-count')->assertStatus(401);
    }

    public function testTeacherCannotEnterStudentOrAdminAreas(): void
    {
        foreach (['student/dashboard', 'student/vote', 'student/my-vote'] as $path) {
            $this->page(self::TEACHER, $path)->assertRedirectTo(site_url('student/login'));
        }

        foreach (['admin/dashboard', 'admin/analytics', 'admin/results', 'admin/teachers', 'admin/unlock'] as $path) {
            $this->page(self::TEACHER, $path)->assertRedirectTo(site_url('admin/login'));
        }

        $this->ajax(self::TEACHER)->get('admin/live-count')->assertStatus(401);
    }

    public function testVotersCannotUseAdminActions(): void
    {
        $actions = [
            ['admin/candidates', ['nomor_urut' => '4', 'nama_ketua' => 'X', 'nama_wakil' => 'Y']],
            ['admin/candidates/1/delete', []],
            ['admin/students/1/status', ['status_aktif' => '0']],
            ['admin/teachers/1/delete', []],
            ['admin/students/import/commit', ['token' => str_repeat('a', 32)]],
            ['admin/election/close', []],
            ['admin/election', ['nama' => 'X', 'tahun' => '2026', 'start_at' => '2026-10-01T07:00', 'end_at' => '2026-10-01T12:00']],
            ['admin/unlock/student/1', ['vote_id' => '1', 'reason' => 'Percobaan tanpa hak akses.', 'confirm' => '1']],
        ];

        foreach ([self::STUDENT, self::TEACHER] as $session) {
            foreach ($actions as [$path, $data]) {
                $this->postAs($session, $path, $data)->assertRedirectTo(site_url('admin/login'));
            }
        }

        $this->seeInDatabase('candidates', ['id' => 1, 'status_aktif' => 1]);
        $this->seeInDatabase('students', ['id' => 1, 'status_aktif' => 1]);
        $this->assertSame(0, $this->db->table('audit_logs')->countAllResults());
        $this->assertSame(0, $this->db->table('candidates')->where('nomor_urut', 4)->countAllResults());
    }

    public function testVoteCapabilitiesPerRole(): void
    {
        // Vote: Yes / Yes / No
        $this->ajax(self::STUDENT)->post('student/vote', ['candidate_id' => 2])->assertStatus(200);
        $this->ajax(self::TEACHER)->post('teacher/vote', ['candidate_id' => 3])->assertStatus(200);
        $this->ajax(self::ADMIN)->post('student/vote', ['candidate_id' => 1, 'student_id' => 2])->assertStatus(401);
        $this->ajax(self::ADMIN)->post('teacher/vote', ['candidate_id' => 1, 'teacher_id' => 2])->assertStatus(401);

        // Re-vote while locked: No / No / No
        $again = $this->ajax(self::STUDENT)->post('student/vote', ['candidate_id' => 1]);
        $again->assertStatus(409);
        $this->assertSame('already_voted', $this->json($again)['status']);
        $this->ajax(self::TEACHER)->post('teacher/vote', ['candidate_id' => 1])->assertStatus(409);

        $this->assertSame(1, $this->db->table('student_votes')->countAllResults());
        $this->assertSame(1, $this->db->table('teacher_votes')->countAllResults());
        $this->seeInDatabase('student_votes', ['student_id' => 1, 'candidate_id' => 2, 'status' => 'LOCKED']);
        $this->seeInDatabase('teacher_votes', ['teacher_id' => 1, 'candidate_id' => 3, 'status' => 'LOCKED']);
    }

    public function testOwnVoteOnlyAndNoResultsForVoters(): void
    {
        // Pemilih lain (siswa 2) memilih 03; siswa 1 memilih 02.
        $this->ajax(['user_type' => 'student', 'student_id' => 2, 'isLoggedIn' => true])->post('student/vote', ['candidate_id' => 3])->assertStatus(200);
        $this->ajax(self::STUDENT)->post('student/vote', ['candidate_id' => 2])->assertStatus(200);

        // View Own Vote: Yes / Yes / N/A
        $mine = $this->page(self::STUDENT, 'student/my-vote');
        $mine->assertStatus(200);
        $mine->assertSee('Bagas Prayoga');
        $mine->assertDontSee('Dewi Anggraini'); // pilihan pemilih lain
        $mine->assertDontSee('Bunga Larasati'); // identitas pemilih lain
        $mine->assertDontSee('suara sah');

        $this->page(self::ADMIN, 'student/my-vote')->assertRedirectTo(site_url('student/login'));

        // View Analytics / hasil: No / No / Yes
        $this->page(self::ADMIN, 'admin/analytics')->assertStatus(200);
        $this->page(self::STUDENT, 'admin/analytics')->assertRedirectTo(site_url('admin/login'));

        // Dasbor pemilih tidak memuat jumlah suara pasangan mana pun.
        $dashboard = $this->page(self::STUDENT, 'student/dashboard');
        $dashboard->assertDontSee('data-live');
        $dashboard->assertDontSee('suara sah');
    }

    public function testViewCandidatesForAllRoles(): void
    {
        foreach ([[self::STUDENT, 'student/vote'], [self::TEACHER, 'teacher/vote'], [self::ADMIN, 'admin/candidates']] as [$session, $path]) {
            $page = $this->page($session, $path);
            $page->assertStatus(200);
            $page->assertSee('Arka Wibisana');
            $page->assertSee('Dewi Anggraini');
        }
    }
}
