<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Identitas visual SMP 1 DAWE (Stage 6, STAGE6-NOTES.md; dokumen 06-10):
 * sistem font aplikasi dan penulisan nama sekolah.
 *
 * @internal
 */
final class VisualIdentityTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    protected function setUp(): void
    {
        parent::setUp();

        cache()->clean();
        // Renderer view dipakai bersama antar-request di proses test
        // (Config\View::$saveData): judul halaman lain tidak boleh terbawa.
        service('renderer')->resetData();
    }

    /**
     * @return array<string, string> halaman => HTML
     */
    private function pages(): array
    {
        $admin = ['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true];

        return [
            'beranda'     => (string) $this->get('/')->response()->getBody(),
            'login siswa' => (string) $this->get('student/login')->response()->getBody(),
            'login admin' => (string) $this->get('admin/login')->response()->getBody(),
            'dasbor'      => (string) $this->withSession($admin)->get('admin/dashboard')->response()->getBody(),
        ];
    }

    public function testUiFontIsPlusJakartaSansAcrossTheApp(): void
    {
        foreach ($this->pages() as $page => $html) {
            $this->assertStringContainsString('assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2', $html, $page);
            $this->assertStringNotContainsString('inter-latin', $html, $page);
        }

        $css = (string) file_get_contents(FCPATH . 'assets/css/app.css');
        $this->assertStringContainsString("--font-ui: 'Plus Jakarta Sans'", $css);
        $this->assertFileExists(FCPATH . 'assets/fonts/plus-jakarta-sans-latin-wght-normal.woff2');
        $this->assertFileExists(FCPATH . 'assets/fonts/OFL-PlusJakartaSans.txt');
        // Tetap tiga family: file Inter dihapus.
        $this->assertFileDoesNotExist(FCPATH . 'assets/fonts/inter-latin-wght-normal.woff2');
    }

    public function testSchoolNameIsWrittenInCapitalsOnEveryPage(): void
    {
        $pages = $this->pages();

        foreach ($pages as $page => $html) {
            $this->assertStringContainsString('SMP 1 DAWE', $html, $page);
            $this->assertDoesNotMatchRegularExpression('/SMP 1 Dawe/', $html, $page);
        }

        // Ditulis literal di sumber, bukan lewat text-transform.
        $this->assertStringContainsString('<title>Pemilihan Ketua &amp; Wakil Ketua OSIS SMP 1 DAWE 2026</title>', $pages['beranda']);
        $this->assertSame('Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 DAWE', $this->db->table('elections')->get()->getRowArray()['nama']);
    }
}
