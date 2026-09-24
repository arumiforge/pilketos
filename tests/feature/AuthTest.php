<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Security\Exceptions\SecurityException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stage 1: login admin/siswa/guru, kredensial salah, proteksi route,
 * sesi, CSRF, dan throttling login.
 *
 * @internal
 */
final class AuthTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    /**
     * POST dengan token CSRF sesi saat ini.
     */
    private function postForm(string $path, array $data)
    {
        return $this->post($path, [csrf_token() => csrf_hash()] + $data);
    }

    public function testHomePageShowsElection(): void
    {
        $result = $this->get('/');

        $result->assertStatus(200);
        $result->assertSee('SMP 1 DAWE');
        $result->assertSee('Sedang Berlangsung');
    }

    public function testStudentLoginWithValidCredential(): void
    {
        $result = $this->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '05062013']);

        $result->assertRedirectTo(site_url('siswa'));
        $this->assertSame('student', session('user_type'));
        $this->assertSame(1, session('student_id'));
        $this->assertTrue(session('isLoggedIn'));
    }

    public function testStudentLoginKeepsLeadingZeroAndAcceptsDateSeparators(): void
    {
        $result = $this->postForm('siswa/masuk', ['nisn' => '0000000003', 'kodeunik' => '01-03-2013']);

        $result->assertRedirectTo(site_url('siswa'));
        $this->assertSame('student', session('user_type'));
    }

    public function testStudentLoginWithInvalidCredential(): void
    {
        $result = $this->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '01012000']);

        $result->assertRedirect();
        $result->assertSessionHas('error');
        $this->assertNull(session('user_type'));
    }

    public function testStudentLoginRejectsMalformedKodeunik(): void
    {
        $result = $this->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => 'abc']);

        $result->assertSessionHas('errors');
        $this->assertNull(session('user_type'));
    }

    public function testInactiveStudentCannotLogin(): void
    {
        $this->postForm('siswa/masuk', ['nisn' => '0000000012', 'kodeunik' => '08032011']);

        $this->assertNull(session('user_type'));
    }

    public function testTeacherLoginWithValidCredential(): void
    {
        $result = $this->postForm('guru/masuk', ['nip' => '000000000000000004', 'kodeunik' => '01061992']);

        $result->assertRedirectTo(site_url('guru'));
        $this->assertSame('teacher', session('user_type'));
        $this->assertSame(4, session('teacher_id'));
    }

    public function testInactiveTeacherCannotLogin(): void
    {
        $this->postForm('guru/masuk', ['nip' => '000000000000000005', 'kodeunik' => '25121988']);

        $this->assertNull(session('user_type'));
    }

    public function testStudentCredentialCannotLoginAsTeacher(): void
    {
        $this->postForm('guru/masuk', ['nip' => '0000000001', 'kodeunik' => '05062013']);

        $this->assertNull(session('user_type'));
    }

    public function testAdminLoginWithValidCredential(): void
    {
        $result = $this->postForm('admin/masuk', ['username' => 'admin', 'password' => 'admin123']);

        $result->assertRedirectTo(site_url('admin'));
        $this->assertSame('admin', session('user_type'));
        $this->assertSame(1, session('admin_id'));
    }

    public function testAdminLoginWithWrongPassword(): void
    {
        $result = $this->postForm('admin/masuk', ['username' => 'admin', 'password' => 'salah']);

        $result->assertSessionHas('error', 'Nama pengguna atau kata sandi tidak sesuai.');
        $this->assertNull(session('user_type'));
    }

    public function testAdminCannotLoginThroughStudentLogin(): void
    {
        $this->postForm('siswa/masuk', ['nisn' => 'admin', 'kodeunik' => 'admin123']);

        $this->assertNull(session('user_type'));
    }

    public function testSessionDoesNotStoreSecrets(): void
    {
        $this->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '05062013']);

        $this->assertNotContains('05062013', array_filter($_SESSION, 'is_string'));
        $this->assertArrayNotHasKey('_ci_old_input', $_SESSION);

        $this->postForm('admin/masuk', ['username' => 'admin', 'password' => 'salah']);

        $this->assertArrayNotHasKey('_ci_old_input', $_SESSION);
        $this->assertSame('admin', session('old_username'));
    }

    public function testLoginAsAnotherRoleClearsPreviousIdentity(): void
    {
        $this->withSession(['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true])
            ->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '05062013']);

        $this->assertSame('student', session('user_type'));
        $this->assertNull(session('admin_id'));
    }

    public function testGuestIsRedirectedFromProtectedRoutes(): void
    {
        $this->get('siswa')->assertRedirectTo(site_url('siswa/masuk'));
        $this->get('guru')->assertRedirectTo(site_url('guru/masuk'));
        $this->get('admin')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testAjaxGuestReceivesJson401(): void
    {
        $result = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get('admin');

        $result->assertStatus(401);
        $result->assertJSONFragment(['status' => 'unauthenticated']);
    }

    public function testStudentCannotAccessTeacherOrAdminRoutes(): void
    {
        $session = ['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true];

        $this->withSession($session)->get('siswa')->assertStatus(200);
        $this->withSession($session)->get('guru')->assertRedirectTo(site_url('guru/masuk'));
        $this->withSession($session)->get('admin')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testTeacherCannotAccessAdminRoutes(): void
    {
        $session = ['user_type' => 'teacher', 'teacher_id' => 1, 'isLoggedIn' => true];

        $this->withSession($session)->get('guru')->assertStatus(200);
        $this->withSession($session)->get('admin')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testStudentSessionIdCannotBeUsedAsTeacherId(): void
    {
        // Sesi siswa yang disisipi teacher_id tetap bukan sesi guru.
        $session = ['user_type' => 'student', 'student_id' => 1, 'teacher_id' => 1, 'isLoggedIn' => true];

        $this->withSession($session)->get('guru')->assertRedirectTo(site_url('guru/masuk'));
    }

    public function testDeactivatedStudentLosesAccessImmediately(): void
    {
        $this->db->table('students')->where('id', 1)->update(['status_aktif' => 0]);

        $result = $this->withSession(['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true])
            ->get('siswa');

        $result->assertRedirectTo(site_url('siswa/masuk'));
        $this->assertNull(session('user_type'));
    }

    public function testDashboardShowsDataFromDatabase(): void
    {
        $result = $this->withSession(['user_type' => 'student', 'student_id' => 3, 'isLoggedIn' => true])
            ->get('siswa');

        $result->assertStatus(200);
        $result->assertSee('Candra Setiawan');
        $result->assertSee('0000000003');
        $result->assertSee('7B');
    }

    public function testLogoutEndsSession(): void
    {
        $this->withSession(['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true])
            ->postForm('siswa/keluar', [])
            ->assertRedirectTo(site_url('/'));

        $this->assertNull(session('user_type'));
    }

    public function testLogoutRequiresPost(): void
    {
        $this->expectException(PageNotFoundException::class);

        $this->withSession(['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true])
            ->get('siswa/keluar');
    }

    public function testPostWithoutCsrfTokenIsRejected(): void
    {
        $this->expectException(SecurityException::class);

        $this->post('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '05062013']);
    }

    public function testLoginIsThrottledPerAccountAfterRepeatedFailures(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->postForm('siswa/masuk', ['nisn' => '0000000002', 'kodeunik' => '0101200' . $i]);
        }

        // Kode benar pun ditolak selama akun sedang diblokir.
        $result = $this->postForm('siswa/masuk', ['nisn' => '0000000002', 'kodeunik' => '17092013']);

        $this->assertStringContainsString('Terlalu banyak percobaan', (string) session('error'));
        $this->assertNull(session('user_type'));

        // Akun lain dari IP yang sama tetap bisa login (satu sekolah berbagi IP).
        $this->postForm('siswa/masuk', ['nisn' => '0000000001', 'kodeunik' => '05062013'])
            ->assertRedirectTo(site_url('siswa'));
    }
}
