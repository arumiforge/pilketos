<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\VoterType;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stage 7: URL bahasa Indonesia santai. URL baru dapat dipakai, URL
 * bahasa Inggris lama tidak dialihkan (404), dan helper path VoterType
 * menghasilkan segmen siswa/guru.
 *
 * @internal
 */
final class IndonesianRoutesTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    private const ADMIN   = ['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true];
    private const STUDENT = ['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true];
    private const TEACHER = ['user_type' => 'teacher', 'teacher_id' => 1, 'isLoggedIn' => true];

    public function testLoginPagesUseMasuk(): void
    {
        foreach (['siswa/masuk' => 'siswa/masuk', 'guru/masuk' => 'guru/masuk', 'admin/masuk' => 'admin/masuk'] as $path => $action) {
            $result = $this->get($path);
            $result->assertStatus(200);
            $result->assertSee('action="' . base_url($action) . '"');
        }
    }

    public function testDashboardsLiveAtRoleRootAndNeedTheirSession(): void
    {
        $this->withSession(self::STUDENT)->get('siswa')->assertStatus(200);
        $this->withSession(self::TEACHER)->get('guru')->assertStatus(200);
        $this->withSession(self::ADMIN)->get('admin')->assertStatus(200);

        $this->withSession([])->get('siswa')->assertRedirectTo(site_url('siswa/masuk'));
        $this->withSession([])->get('guru')->assertRedirectTo(site_url('guru/masuk'));
        $this->withSession([])->get('admin')->assertRedirectTo(site_url('admin/masuk'));
    }

    public function testVoterNavigationUsesIndonesianPaths(): void
    {
        $dashboard = $this->withSession(self::STUDENT)->get('siswa');

        $dashboard->assertSee('href="' . base_url('siswa') . '"');
        $dashboard->assertSee('action="' . base_url('siswa/keluar') . '"');
        $dashboard->assertSee('href="' . base_url('siswa/coblos') . '"');

        $admin = $this->withSession(self::ADMIN)->get('admin');
        foreach (['admin/hitung-suara', 'admin/analitik', 'admin/analitik/suara', 'admin/hasil', 'admin/paslon', 'admin/siswa', 'admin/guru', 'admin/jadwal', 'admin/buka-kunci', 'admin/riwayat', 'admin/keluar'] as $path) {
            $admin->assertSee(site_url($path));
        }
    }

    public function testVoterTypePathHelpersUseSlug(): void
    {
        $this->assertSame('siswa', VoterType::Student->slug());
        $this->assertSame('guru', VoterType::Teacher->slug());
        $this->assertSame('siswa/coblos', VoterType::Student->path('coblos'));
        $this->assertSame('guru', VoterType::Teacher->path());
        $this->assertSame('admin/siswa/impor', VoterType::Student->adminPath('impor'));
        $this->assertSame('admin/guru', VoterType::Teacher->adminPath());
        $this->assertSame('admin/buka-kunci/guru/7', VoterType::Teacher->unlockPath(7));
        // Nilai internal (sesi & database) tidak berubah.
        $this->assertSame('student', VoterType::Student->value);
    }

    public function testOldEnglishUrlsAreGone(): void
    {
        $old = [
            'student', 'student/login', 'student/dashboard', 'student/vote', 'student/my-vote',
            'teacher', 'teacher/login', 'teacher/dashboard', 'teacher/vote',
            'admin/login', 'admin/dashboard', 'admin/live-count', 'admin/analytics', 'admin/results',
            'admin/candidates', 'admin/students', 'admin/students/import', 'admin/teachers',
            'admin/election', 'admin/unlock', 'admin/audit', 'live-count', 'election/clock',
        ];

        foreach ($old as $path) {
            try {
                $this->withSession(self::ADMIN)->get($path);
                $this->fail("GET {$path} seharusnya 404");
            } catch (PageNotFoundException) {
                $this->addToAssertionCount(1);
            }
        }
    }
}
