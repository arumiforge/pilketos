<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Libraries\CandidateAssets;
use App\Services\Import\ImportStore;
use App\Services\Import\StudentImporter;
use App\Services\Import\TeacherImporter;
use App\Services\Import\VoterImporter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\SpreadsheetFactory;
use Tests\Support\UploadFixture;

/**
 * Stage 4: verifikasi impor (04-FINAL... bagian 10) dalam satu tempat.
 *
 * Kasus: empty Excel, invalid header, duplicate NISN, duplicate NIP, invalid
 * gender, empty name, missing class, invalid code, leading zero, large file,
 * repeated import. Sebagian juga diuji rinci di VoterImportTest (Stage 3);
 * di sini tiap kasus dipastikan tidak pernah menghasilkan data ganda atau
 * data rusak di database, termasuk lewat alur HTTP lengkap.
 *
 * @internal
 */
final class ImportVerificationTest extends CIUnitTestCase
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

        $this->storeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import-verify-' . bin2hex(random_bytes(4));
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

    private function keep(string $path): string
    {
        $this->files[] = $path;

        return $path;
    }

    private function parseStudents(array $rows): array
    {
        return (new StudentImporter($this->db))->parse($this->keep(SpreadsheetFactory::students($rows)));
    }

    private function parseTeachers(array $rows): array
    {
        return (new TeacherImporter($this->db))->parse($this->keep(SpreadsheetFactory::teachers($rows)));
    }

    private function errorsOf(array $result, int $excelRow): string
    {
        foreach ($result['rows'] as $row) {
            if ($row['row'] === $excelRow) {
                return implode(' ', $row['errors']);
            }
        }

        return '';
    }

    private function upload(string $role, string $path)
    {
        UploadFixture::attach(['file' => ['path' => $this->keep($path), 'name' => basename($path)]]);

        return $this->withSession(['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true])
            ->post('admin/' . $role . '/impor', [csrf_token() => csrf_hash()]);
    }

    /**
     * Unggah -> pratinjau -> impor lewat HTTP. Mengembalikan [token, respons commit].
     */
    private function commitThroughHttp(string $role, string $path): array
    {
        $upload = $this->upload($role, $path);
        $token  = substr($upload->response()->getHeaderLine('Location'), -32);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        $commit = $this->withSession(['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true])
            ->post('admin/' . $role . '/impor/simpan', [csrf_token() => csrf_hash(), 'token' => $token, 'confirm_skip' => '1']);

        return [$token, $commit];
    }

    private function importThroughHttp(string $role, string $path): void
    {
        [, $commit] = $this->commitThroughHttp($role, $path);
        $commit->assertRedirectTo(site_url('admin/' . $role . '/impor/selesai'));
    }

    // ------------------------------------------------------------------

    public function testEmptyWorkbookAndInvalidHeaderAreRejectedWholesale(): void
    {
        $importer = new StudentImporter($this->db);

        $this->assertSame(['File kosong: sheet pertama tidak berisi data.'], $importer->parse($this->keep(SpreadsheetFactory::write([])))['errors']);

        $onlyHeader = $importer->parse($this->keep(SpreadsheetFactory::students([])));
        $this->assertSame(['Tidak ada baris data di bawah baris judul.'], $onlyHeader['errors']);

        $wrong = $importer->parse($this->keep(SpreadsheetFactory::write([['no', 'NIS', 'nama', 'jk', 'kelas'], [1, '0012345678', 'A', 'L', '7A']])));
        $this->assertStringContainsString('Header tidak sesuai template', $wrong['errors'][0]);
        $this->assertSame([], $wrong['rows']);
    }

    public function testDuplicateNisnAndDuplicateNipInFileAreBothRejected(): void
    {
        $students = $this->parseStudents([
            [1, '0012345678', 'Kembar Satu', 'L', '7A', 1, '01032013'],
            [2, '0012345678', 'Kembar Dua', 'P', '7B', 2, '02032013'],
        ]);
        $this->assertSame(2, $students['summary']['invalid']);
        $this->assertStringContainsString('NISN 0012345678 ganda di file', $this->errorsOf($students, 2));

        $teachers = $this->parseTeachers([
            [1, '198501012010011099', 'Guru Kembar', '01011985'],
            [2, '198501012010011099', 'Guru Kembar Lagi', '02021986'],
            [3, '198601012010011001', 'Guru Unik', '03031986'],
        ]);
        $this->assertSame(2, $teachers['summary']['invalid']);
        $this->assertSame(1, $teachers['summary']['importable']);
        $this->assertStringContainsString('NIP 198501012010011099 ganda di file', $this->errorsOf($teachers, 2));
        $this->assertStringContainsString('NIP 198501012010011099 ganda di file', $this->errorsOf($teachers, 3));

        // Commit hanya memasukkan baris sah; tidak ada NIP ganda di database.
        (new TeacherImporter($this->db))->commit($teachers['rows']);
        $this->assertSame(0, $this->db->table('teachers')->where('nip', '198501012010011099')->countAllResults());
        $this->assertSame(1, $this->db->table('teachers')->where('nip', '198601012010011001')->countAllResults());
    }

    public function testInvalidGenderEmptyNameMissingClassAndInvalidCodeAreRejectedPerRow(): void
    {
        $result = $this->parseStudents([
            [1, '0012345601', 'Gender Salah', 'W', '7A', 1, '01032013'],
            [2, '0012345602', '', 'L', '7A', 2, '01032013'],
            [3, '0012345603', 'Tanpa Kelas', 'P', '', 3, '01032013'],
            [4, '0012345604', 'Kode Salah', 'L', '7A', 4, '32132013'],
            [5, '0012345605', 'Sah', 'P', '8B', 5, '05052012'],
        ]);

        $this->assertStringContainsString('Jenis kelamin harus L atau P', $this->errorsOf($result, 2));
        $this->assertStringContainsString('Nama wajib diisi', $this->errorsOf($result, 3));
        $this->assertStringContainsString('Rombel wajib diisi', $this->errorsOf($result, 4));
        $this->assertStringContainsString('Kode unik harus tanggal lahir DDMMYYYY', $this->errorsOf($result, 5));
        $this->assertSame(4, $result['summary']['invalid']);
        $this->assertSame(1, $result['summary']['importable']);

        $teacher = $this->parseTeachers([[1, '198501012010011005', '', 'abc']]);
        $this->assertStringContainsString('Nama wajib diisi', $this->errorsOf($teacher, 2));
        $this->assertStringContainsString('Kode unik harus tanggal lahir DDMMYYYY', $this->errorsOf($teacher, 2));
    }

    public function testLeadingZerosSurviveUntilLogin(): void
    {
        $path = SpreadsheetFactory::students([
            [1, 12345679, 'Nol Hilang Excel', 'L', '7A', 1, 1032013], // sel angka: 0012345679 / 01032013
            [2, '0012345680', 'Teks Aman', 'P', '7A', 2, '02032013'],
        ]);
        $this->importThroughHttp('siswa', $path);

        $this->seeInDatabase('students', ['nisn' => '0012345679', 'kodeunik' => '01032013']);
        $this->seeInDatabase('students', ['nisn' => '0012345680', 'kodeunik' => '02032013']);

        $this->withSession([])->post('siswa/masuk', [csrf_token() => csrf_hash(), 'nisn' => '0012345679', 'kodeunik' => '01032013'])
            ->assertRedirectTo(site_url('siswa'));
    }

    public function testLargeFilesAreRejectedByRowsAndBytes(): void
    {
        $rows = [];
        for ($i = 1; $i <= VoterImporter::MAX_ROWS + 1; $i++) {
            $rows[] = [$i, sprintf('%010d', 5000000 + $i), 'Siswa ' . $i, 'L', '7A', null, '01032013'];
        }
        $tooMany = $this->parseStudents($rows);
        $this->assertStringContainsString('lebih dari 3.000 baris', $tooMany['errors'][0]);

        // File melebihi batas byte ditolak sebelum dibaca PhpSpreadsheet.
        $limit = CandidateAssets::iniBytes((string) ini_get('upload_max_filesize'));
        $max   = $limit > 0 ? min(VoterImporter::MAX_BYTES, $limit) : VoterImporter::MAX_BYTES;
        $big   = tempnam(sys_get_temp_dir(), 'big') . '.xlsx';
        file_put_contents($big, str_repeat('x', $max + 1024));

        $this->upload('siswa', $big)->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('Ukuran file maksimal', (string) session('error'));
        $this->assertSame(12, $this->db->table('students')->countAllResults());
    }

    public function testRepeatedImportNeverDuplicates(): void
    {
        $rows = [
            [1, '0077700001', 'Ulang Satu', 'L', '9A', 1, '01012011'],
            [2, '0077700002', 'Ulang Dua', 'P', '9A', 2, '02012011'],
        ];

        $this->importThroughHttp('siswa', SpreadsheetFactory::students($rows));

        // Impor ulang file yang sama: semua "tidak berubah", tidak ada yang ditulis.
        [$token, $again] = $this->commitThroughHttp('siswa', SpreadsheetFactory::students($rows));
        $again->assertRedirectTo(site_url('admin/siswa/impor/cek/' . $token));
        $this->assertStringContainsString('Tidak ada baris baru atau berubah', (string) session('error'));

        // Data berubah pada impor berikutnya = upsert (nama diperbarui), tetap satu baris.
        $rows[1][2] = 'Ulang Dua Diperbarui';
        $this->importThroughHttp('siswa', SpreadsheetFactory::students($rows));

        $this->assertSame(1, $this->db->table('students')->where('nisn', '0077700001')->countAllResults());
        $this->assertSame(1, $this->db->table('students')->where('nisn', '0077700002')->countAllResults());
        $this->seeInDatabase('students', ['nisn' => '0077700002', 'name' => 'Ulang Dua Diperbarui']);

        $audits = $this->db->table('audit_logs')->where('action', 'IMPORT_STUDENT')->orderBy('id')->get()->getResultArray();
        $this->assertCount(2, $audits);
        $this->assertStringContainsString('2 baru, 0 diperbarui', $audits[0]['description']);
        $this->assertStringContainsString('0 baru, 1 diperbarui, 1 tidak berubah', $audits[1]['description']);
    }
}
