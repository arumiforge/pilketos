<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\Import\ImportStore;
use App\Services\VoterType;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\Support\SpreadsheetFactory;
use Tests\Support\UploadFixture;

/**
 * Stage 3: alur impor Excel lewat HTTP:
 * unduh template -> unggah -> pratinjau -> impor -> hasil (+ audit).
 *
 * @internal
 */
final class AdminImportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    private string $storeDir;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->storeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import-http-' . bin2hex(random_bytes(4));
        Services::injectMock('importStore', new ImportStore($this->storeDir));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->storeDir . '/*') ?: [] as $file) {
            unlink($file);
        }
        @rmdir($this->storeDir);

        foreach ($this->files as $file) {
            @unlink($file);
        }

        UploadFixture::reset();
        parent::tearDown();
    }

    private function admin(int $id = 1): array
    {
        return ['user_type' => 'admin', 'admin_id' => $id, 'isLoggedIn' => true];
    }

    private function upload(string $role, string $path, string $name = 'data.xlsx')
    {
        $this->files[] = $path;
        UploadFixture::attach(['file' => ['path' => $path, 'name' => $name]]);

        return $this->withSession($this->admin())->post('admin/' . $role . '/impor', [csrf_token() => csrf_hash()]);
    }

    private function tokenFrom($result): string
    {
        $location = $result->response()->getHeaderLine('Location');
        $this->assertMatchesRegularExpression('#/impor/cek/([a-f0-9]{32})$#', $location);

        return substr($location, -32);
    }

    public function testTemplateDownloadIsTheStudentTemplate(): void
    {
        $result = $this->withSession($this->admin())->get('admin/siswa/impor/templat');
        // Header unduhan dibentuk saat respons dikirim; di test dibentuk manual.
        $result->response()->buildHeaders();

        $result->assertStatus(200);
        $this->assertStringContainsString('templat-impor-siswa.xlsx', $result->response()->getHeaderLine('Content-Disposition'));
        $this->assertStringContainsString('spreadsheetml', $result->response()->getHeaderLine('Content-Type'));

        // DownloadResponse menulis isi file saat dikirim; tangkap output-nya.
        ob_start();
        $result->response()->sendBody();
        $binary = (string) ob_get_clean();

        $path = tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx';
        $this->files[] = $path;
        file_put_contents($path, $binary);
        $sheet = (new Xlsx())->load($path)->getSheet(0);
        $this->assertSame([['no', 'NISN', 'nama', 'jenis_kelamin', 'rombel', 'nomor_absen', 'kodeunik']], $sheet->rangeToArray('A1:G1'));

        $teacher = $this->withSession($this->admin())->get('admin/guru/impor/templat');
        $teacher->response()->buildHeaders();
        $this->assertStringContainsString('templat-impor-guru.xlsx', $teacher->response()->getHeaderLine('Content-Disposition'));
    }

    public function testStudentImportFlowPreviewCommitResultAndAudit(): void
    {
        $upload = $this->upload('siswa', SpreadsheetFactory::students([
            [1, '0012345678', 'Rizky Amelia', 'P', '7C', 3, '01032013'],
            [2, 12345679, 'Nol Hilang', 'L', '7C', 4, '02032013'],                 // leading zero dipulihkan
            [3, '0000000002', 'Bunga Larasati Putri', 'P', '7A', 2, '17092013'],  // diperbarui
            [4, '0000000001', 'Ahmad Fauzan', 'L', '7A', 1, '05062013'],          // tidak berubah
            [5, 'ABC', 'Salah NISN', 'X', '12Z', 1, '99999999'],                  // bermasalah
        ]), 'siswa kelas 7.xlsx');

        $token = $this->tokenFrom($upload);
        $this->seeInDatabase('students', ['nisn' => '0000000002', 'name' => 'Bunga Larasati']); // belum disimpan

        $preview = $this->withSession($this->admin())->get('admin/siswa/impor/cek/' . $token);
        $preview->assertStatus(200);
        $preview->assertSee('siswa kelas 7.xlsx');
        $preview->assertSee('Perlu diperiksa');
        $preview->assertSee('NISN hanya boleh berisi angka');
        $preview->assertSee('dipulihkan menjadi 0012345679');
        $preview->assertSee('name="confirm_skip"');

        $all = $this->withSession($this->admin())->get('admin/siswa/impor/cek/' . $token . '?show=all');
        $all->assertSee('Rizky Amelia');
        $all->assertSee('Berubah: nama');

        // Ada baris bermasalah: konfirmasi wajib dicentang.
        $this->withSession($this->admin())->post('admin/siswa/impor/simpan', [csrf_token() => csrf_hash(), 'token' => $token])
            ->assertRedirectTo(site_url('admin/siswa/impor/cek/' . $token));
        $this->dontSeeInDatabase('students', ['nisn' => '0012345678']);

        $commit = $this->withSession($this->admin())->post('admin/siswa/impor/simpan', [
            csrf_token()   => csrf_hash(),
            'token'        => $token,
            'confirm_skip' => '1',
        ]);
        $commit->assertRedirectTo(site_url('admin/siswa/impor/selesai'));

        $this->seeInDatabase('students', ['nisn' => '0012345678', 'name' => 'Rizky Amelia', 'kelas' => '7C', 'status_aktif' => 1]);
        $this->seeInDatabase('students', ['nisn' => '0012345679', 'name' => 'Nol Hilang']);
        $this->seeInDatabase('students', ['nisn' => '0000000002', 'name' => 'Bunga Larasati Putri']);
        $this->assertSame(14, $this->db->table('students')->countAllResults());

        $audit = $this->db->table('audit_logs')->where('action', 'IMPORT_STUDENT')->get()->getRowArray();
        $this->assertStringContainsString('"siswa kelas 7.xlsx": 5 baris dibaca, 2 baru, 1 diperbarui, 1 tidak berubah, 1 dilewati', $audit['description']);

        $result = $this->withSession()->get('admin/siswa/impor/selesai');
        $result->assertStatus(200);
        $result->assertSee('Impor selesai');
        $result->assertSee('Ditambahkan');

        // Token sekali pakai.
        $this->withSession($this->admin())->get('admin/siswa/impor/cek/' . $token)
            ->assertRedirectTo(site_url('admin/siswa/impor'));
    }

    public function testTeacherImportFlow(): void
    {
        $token = $this->tokenFrom($this->upload('guru', SpreadsheetFactory::teachers([
            [1, '198501012010011001', 'Guru Baru, S.Pd.', '01011985'],
            [2, '000000000000000004', 'Siti Nur Aini, M.Pd.', '01061992'],
        ])));

        $this->withSession($this->admin())->post('admin/guru/impor/simpan', [csrf_token() => csrf_hash(), 'token' => $token])
            ->assertRedirectTo(site_url('admin/guru/impor/selesai'));

        $this->seeInDatabase('teachers', ['nip' => '198501012010011001', 'name' => 'Guru Baru, S.Pd.']);
        $this->seeInDatabase('teachers', ['nip' => '000000000000000004', 'name' => 'Siti Nur Aini, M.Pd.']);
        $this->seeInDatabase('audit_logs', ['action' => 'IMPORT_TEACHER']);
        $this->assertSame(0, $this->db->table('students')->where('nisn', '198501012010011001')->countAllResults());
    }

    public function testInvalidFilesAreRejectedBeforePreview(): void
    {
        // Header salah.
        $this->upload('siswa', SpreadsheetFactory::write([['no', 'NIP', 'nama', 'kodeunik'], [1, '1985', 'X', '01011985']]))
            ->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('Kolom wajib tidak ditemukan', implode(' ', session('errors')));

        // Bukan file Excel.
        $csv = tempnam(sys_get_temp_dir(), 'csv');
        file_put_contents($csv, "no,NISN,nama\n1,0012345678,CSV");
        $this->upload('siswa', $csv, 'siswa.csv')->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('Format file harus Excel .xlsx', (string) session('error'));

        // Tanpa file.
        UploadFixture::reset();
        $this->withSession($this->admin())->post('admin/siswa/impor', [csrf_token() => csrf_hash()])
            ->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('Pilih file Excel', (string) session('error'));

        $this->assertSame([], glob($this->storeDir . '/*.json') ?: []);
    }

    public function testPreviewTokenIsBoundToTheUploadingAdmin(): void
    {
        $this->db->table('admins')->insert(['name' => 'Admin Dua', 'username' => 'admin2', 'password_hash' => password_hash('rahasia-dua', PASSWORD_DEFAULT)]);

        $token = $this->tokenFrom($this->upload('siswa', SpreadsheetFactory::students([
            [1, '0012345678', 'Siswa A', 'L', '8C', 1, '01032012'],
        ])));

        $this->withSession($this->admin(2))->get('admin/siswa/impor/cek/' . $token)
            ->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->withSession($this->admin(2))->post('admin/siswa/impor/simpan', [csrf_token() => csrf_hash(), 'token' => $token])
            ->assertRedirectTo(site_url('admin/siswa/impor'));

        // Token siswa tidak berlaku untuk impor guru.
        $this->withSession($this->admin())->get('admin/guru/impor/cek/' . $token)
            ->assertRedirectTo(site_url('admin/guru/impor'));

        $this->dontSeeInDatabase('students', ['nisn' => '0012345678']);
        $this->assertNotNull(service('importStore')->load($token, 1, VoterType::Student));
    }

    public function testImportPageExplainsTemplateAndLimits(): void
    {
        $result = $this->withSession($this->admin())->get('admin/siswa/impor');

        $result->assertStatus(200);
        $result->assertSee('templat-impor-siswa.xlsx');
        $result->assertSee('href="' . site_url('admin/siswa/impor/templat') . '"');
        $result->assertSee('enctype="multipart/form-data"');
        $result->assertSee('NISN tepat 10 digit');
    }
}
