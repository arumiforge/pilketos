<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\Import\ImportStore;
use App\Services\Import\StudentImporter;
use App\Services\Import\TeacherImporter;
use App\Services\Import\VoterImporter;
use App\Services\VoterType;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use Tests\Support\SpreadsheetFactory;

/**
 * Stage 3: import Excel siswa & guru (PhpSpreadsheet) langsung ke MySQL.
 * Checklist 04-FINAL bagian 10 (empty, header, duplikat, gender, nama,
 * kelas, kode, leading zero, file besar, impor ulang) diuji di sini.
 *
 * @internal
 */
final class VoterImportTest extends CIUnitTestCase
{
    use DatabaseTestTrait;

    protected $migrate   = true;
    protected $refresh   = true;
    protected $namespace = 'App';
    protected $seed      = DatabaseSeeder::class;

    /**
     * @var list<string>
     */
    private array $files = [];

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }

        parent::tearDown();
    }

    private function file(string $path): string
    {
        $this->files[] = $path;

        return $path;
    }

    private function students(array $rows): array
    {
        return (new StudentImporter($this->db))->parse($this->file(SpreadsheetFactory::students($rows)));
    }

    private function teachers(array $rows): array
    {
        return (new TeacherImporter($this->db))->parse($this->file(SpreadsheetFactory::teachers($rows)));
    }

    private function row(array $result, int $excelRow): array
    {
        foreach ($result['rows'] as $row) {
            if ($row['row'] === $excelRow) {
                return $row;
            }
        }

        $this->fail('Baris ' . $excelRow . ' tidak ada di hasil import');
    }

    // ------------------------------------------------------------------
    // template
    // ------------------------------------------------------------------

    public function testStudentTemplateHasExactHeadersAndTextColumns(): void
    {
        $importer = new StudentImporter($this->db);
        $path     = $this->file(tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx');
        file_put_contents($path, $importer->templateBinary());

        $spreadsheet = (new Xlsx())->load($path);
        $sheet       = $spreadsheet->getSheet(0);

        $this->assertSame('templat-impor-siswa.xlsx', $importer->templateFilename());
        $this->assertSame(
            [['no', 'NISN', 'nama', 'jenis_kelamin', 'kelas', 'nomor_absen', 'kodeunik']],
            $sheet->rangeToArray('A1:G1'),
        );
        // Kolom NISN & kodeunik berformat Teks (style kolom, berlaku untuk sel
        // yang diketik admin) agar leading zero aman.
        $columnFormat = static fn (string $column): string => $spreadsheet
            ->getCellXfByIndex($sheet->getColumnDimension($column)->getXfIndex())
            ->getNumberFormat()
            ->getFormatCode();
        $this->assertSame('@', $columnFormat('B'));
        $this->assertSame('@', $columnFormat('G'));
        $this->assertSame('A2', $sheet->getSelectedCells());
        $this->assertSame(array_fill(0, 3, array_fill(0, 7, null)), $sheet->rangeToArray('A2:G4'), 'Template tidak boleh berisi baris data contoh.');
        $this->assertSame('Petunjuk', $spreadsheet->getSheet(1)->getTitle());
        $this->assertSame('list', $sheet->getDataValidation('D2')->getType());

        // Template yang masih kosong ditolak dengan pesan jelas.
        $this->assertSame(['Tidak ada baris data di bawah baris judul.'], $importer->parse($path)['errors']);
        $spreadsheet->disconnectWorksheets();
    }

    public function testTeacherTemplateHeaders(): void
    {
        $importer = new TeacherImporter($this->db);
        $path     = $this->file(tempnam(sys_get_temp_dir(), 'tpl') . '.xlsx');
        file_put_contents($path, $importer->templateBinary());

        $spreadsheet = (new Xlsx())->load($path);
        $sheet       = $spreadsheet->getSheet(0);

        $this->assertSame('templat-impor-guru.xlsx', $importer->templateFilename());
        $this->assertSame([['no', 'NIP', 'nama', 'kodeunik']], $sheet->rangeToArray('A1:D1'));
        $this->assertSame('@', $spreadsheet->getCellXfByIndex($sheet->getColumnDimension('B')->getXfIndex())->getNumberFormat()->getFormatCode());
        $spreadsheet->disconnectWorksheets();
    }

    // ------------------------------------------------------------------
    // parse siswa
    // ------------------------------------------------------------------

    public function testValidStudentRowsArePreviewedAsCreateOrUpdate(): void
    {
        $result = $this->students([
            [1, '0012345678', 'Rizky  Amelia', 'P', '7c', 3, '01032013'],
            [2, '0000000001', 'Ahmad Fauzan', 'L', '7A', 1, '05062013'],          // sama persis
            [3, '0000000002', 'Bunga Larasati Putri', 'P', '7A', 2, '17092013'],  // nama berubah
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(
            ['rows' => 3, 'create' => 1, 'update' => 1, 'same' => 1, 'invalid' => 0, 'warnings' => 0, 'issues' => 0, 'importable' => 2],
            $result['summary'],
        );

        $new = $this->row($result, 2);
        $this->assertSame('create', $new['action']);
        $this->assertSame(['nisn' => '0012345678', 'name' => 'Rizky Amelia', 'jenis_kelamin' => 'P', 'kelas' => '7C', 'nomor_absen' => 3, 'kodeunik' => '01032013'], $new['values']);

        $this->assertSame('same', $this->row($result, 3)['action']);

        $changed = $this->row($result, 4);
        $this->assertSame('update', $changed['action']);
        $this->assertSame(['name' => ['Bunga Larasati', 'Bunga Larasati Putri']], $changed['changes']);
    }

    public function testLeadingZerosAreRestoredFromNumberCellsWithWarning(): void
    {
        $result = $this->students([
            // NISN angka biasa: nol depan hilang -> dipulihkan ke 10 digit + peringatan.
            [1, 12345678, 'Siswa Angka', 'L', '8A', 1, 1032013],
            // NISN angka berformat "0000000000": tampilan benar dipakai, tanpa peringatan NISN.
            [2, ['number' => 23456789, 'format' => '0000000000'], 'Siswa Format', 'P', '8A', 2, '02032013'],
            // Kode unik diubah Excel menjadi tanggal.
            [3, '0034567890', 'Siswa Tanggal', 'L', '8A', 3, ['date' => '2013-03-01']],
            // Kode unik diketik dengan pemisah.
            [4, '0045678901', 'Siswa Pemisah', 'P', '8A', 4, '01-03-2013'],
        ]);

        $this->assertSame([], $result['errors']);

        $numeric = $this->row($result, 2);
        $this->assertSame('0012345678', $numeric['values']['nisn']);
        $this->assertSame('01032013', $numeric['values']['kodeunik']);
        $this->assertCount(2, $numeric['warnings']);
        $this->assertStringContainsString('dipulihkan menjadi 0012345678', $numeric['warnings'][0]);

        $formatted = $this->row($result, 3);
        $this->assertSame('0023456789', $formatted['values']['nisn']);
        $this->assertSame([], $formatted['warnings']);

        $this->assertSame('01032013', $this->row($result, 4)['values']['kodeunik']);
        $this->assertSame('01032013', $this->row($result, 5)['values']['kodeunik']);
        $this->assertSame(4, $result['summary']['create']);
    }

    public function testInvalidStudentRowsAreRejectedWithReasons(): void
    {
        $result = $this->students([
            [1, '12345', 'NISN Pendek', 'L', '7A', 1, '01032013'],
            [2, '00123A5678', 'NISN Huruf', 'L', '7A', 2, '01032013'],
            [3, '0012345679', '', 'L', '7A', 3, '01032013'],
            [4, '0012345680', 'Gender Salah', 'X', '7A', 4, '01032013'],
            [5, '0012345681', 'Tanpa Kelas', 'P', '', 5, '01032013'],
            [6, '0012345682', 'Kelas Salah', 'P', '10A', 6, '01032013'],
            [7, '0012345683', 'Kode Salah', 'L', '7A', 7, '31022013'],
            [8, '0012345684', 'Absen Salah', 'L', '7A', 'tiga', '01032013'],
            [9, '0012345685', 'Kode Masa Depan', 'L', '7A', 9, '01012999'],
        ]);

        $this->assertSame(9, $result['summary']['invalid']);
        $this->assertSame(0, $result['summary']['importable']);

        $expect = [
            2  => 'NISN harus 10 digit',
            3  => 'NISN hanya boleh berisi angka',
            4  => 'Nama wajib diisi',
            5  => 'Jenis kelamin harus L atau P',
            6  => 'Kelas wajib diisi',
            7  => 'Kelas harus diawali jenjang 7, 8, atau 9',
            8  => 'Kode unik harus tanggal lahir DDMMYYYY',
            9  => 'Nomor absen harus angka bulat',
            10 => 'Kode unik harus tanggal lahir DDMMYYYY',
        ];

        foreach ($expect as $excelRow => $message) {
            $row = $this->row($result, $excelRow);
            $this->assertSame('invalid', $row['action']);
            $this->assertStringContainsString($message, implode(' ', $row['errors']), 'Baris ' . $excelRow);
        }
    }

    public function testGenderSynonymsAndOptionalAbsen(): void
    {
        $result = $this->students([
            [1, '0012345678', 'Laki Satu', 'Laki-laki', 'VII A', null, '01032013'],
            [2, '0012345679', 'Perempuan Satu', 'perempuan', 'ix-b', '', '02032013'],
        ]);

        $this->assertSame([], $result['errors']);
        $this->assertSame(['L', 'VII A', null], [$this->row($result, 2)['values']['jenis_kelamin'], $this->row($result, 2)['values']['kelas'], $this->row($result, 2)['values']['nomor_absen']]);
        $this->assertSame(['P', 'IX-B'], [$this->row($result, 3)['values']['jenis_kelamin'], $this->row($result, 3)['values']['kelas']]);
    }

    public function testDuplicateNisnInFileRejectsAllCopies(): void
    {
        $result = $this->students([
            [1, '0012345678', 'Kembar Satu', 'L', '7A', 1, '01032013'],
            [2, '0012345679', 'Unik', 'P', '7A', 2, '02032013'],
            [3, '0012345678', 'Kembar Dua', 'L', '7B', 1, '03032013'],
        ]);

        $this->assertSame(2, $result['summary']['invalid']);
        $this->assertStringContainsString('ganda di file (baris 2, 4)', $this->row($result, 2)['errors'][0]);
        $this->assertStringContainsString('ganda di file (baris 2, 4)', $this->row($result, 4)['errors'][0]);
        $this->assertSame('create', $this->row($result, 3)['action']);
    }

    public function testDuplicateAbsenInClassIsOnlyAWarning(): void
    {
        $result = $this->students([
            [1, '0012345678', 'Satu', 'L', '7A', 5, '01032013'],
            [2, '0012345679', 'Dua', 'P', '7a', 5, '02032013'],
        ]);

        $this->assertSame(2, $result['summary']['importable']);
        $this->assertSame(2, $result['summary']['warnings']);
        $this->assertStringContainsString('Nomor absen 5 di kelas 7A', $this->row($result, 3)['warnings'][0]);
    }

    public function testHeaderValidation(): void
    {
        $importer = new StudentImporter($this->db);

        $missing = $importer->parse($this->file(SpreadsheetFactory::write([
            ['no', 'NISN', 'nama', 'kelas', 'kodeunik'],
            [1, '0012345678', 'Tanpa JK', '7A', '01032013'],
        ])));
        $this->assertStringContainsString('Kolom wajib tidak ditemukan: jenis_kelamin, nomor_absen', $missing['errors'][0]);

        $wrongFile = $importer->parse($this->file(SpreadsheetFactory::write([
            ['no', 'NIP', 'nama', 'kodeunik'],
            [1, '198501012010011001', 'Guru', '01011985'],
        ])));
        $this->assertStringContainsString('Kolom wajib tidak ditemukan: NISN', $wrongFile['errors'][0]);

        $duplicate = $importer->parse($this->file(SpreadsheetFactory::write([
            ['no', 'NISN', 'nama', 'jenis_kelamin', 'kelas', 'nomor_absen', 'kodeunik', 'NISN'],
            [1, '0012345678', 'A', 'L', '7A', 1, '01032013', '0012345678'],
        ])));
        $this->assertStringContainsString('muncul lebih dari sekali: NISN', implode(' ', $duplicate['errors']));

        // Header berbeda gaya penulisan + baris judul di atasnya + kolom tambahan tetap diterima.
        $styled = $importer->parse($this->file(SpreadsheetFactory::write([
            ['DATA SISWA KELAS 7'],
            [],
            ['No', 'nisn', 'Nama', 'Jenis Kelamin', 'KELAS', 'Nomor Absen', 'Kode Unik', 'Keterangan'],
            [1, '0012345678', 'Gaya Header', 'L', '7A', 1, '01032013', 'pindahan'],
        ])));
        $this->assertSame([], $styled['errors']);
        $this->assertSame(['Kolom tambahan diabaikan: Keterangan.'], $styled['notices']);
        $this->assertSame(4, $styled['rows'][0]['row']);
    }

    public function testEmptyAndNonExcelFilesAreRejected(): void
    {
        $importer = new StudentImporter($this->db);

        $empty = $importer->parse($this->file(SpreadsheetFactory::write([])));
        $this->assertSame(['File kosong: sheet pertama tidak berisi data.'], $empty['errors']);

        $fake = $this->file(tempnam(sys_get_temp_dir(), 'fake') . '.xlsx');
        file_put_contents($fake, "no,NISN,nama\n1,0012345678,CSV");
        $this->assertSame(['File bukan workbook Excel .xlsx yang valid.'], $importer->parse($fake)['errors']);
    }

    public function testFileOverRowLimitIsRejected(): void
    {
        $rows = [];
        for ($i = 1; $i <= VoterImporter::MAX_ROWS + 1; $i++) {
            $rows[] = [$i, sprintf('%010d', 1000000 + $i), 'Siswa ' . $i, $i % 2 ? 'L' : 'P', '7A', null, '01032013'];
        }

        $result = $this->students($rows);

        $this->assertStringContainsString('lebih dari 3.000 baris', $result['errors'][0]);
        $this->assertSame([], $result['rows']);
    }

    // ------------------------------------------------------------------
    // parse guru
    // ------------------------------------------------------------------

    public function testTeacherNipRules(): void
    {
        $result = $this->teachers([
            [1, '19850101 201001 1 001', 'Guru Spasi', '01011985'],       // spasi dibuang
            [2, 198501012010011001, 'Guru Angka 18', '01011985'],          // presisi hilang: ditolak
            [3, 1234567890123456, 'Guru NUPTK Angka', '02021980'],       // 16 digit angka: ditolak
            [4, '1234567890123456', 'Guru NUPTK Teks', '02021980'],      // 16 digit teks: peringatan
            [5, '000000000000000001', 'Sudarmanto, S.Pd.', '01011985'],  // sudah ada, sama
            [6, '19860202 201001 1 002', '', '99999999'],
        ]);

        $this->assertSame('198501012010011001', $this->row($result, 2)['values']['nip']);
        $this->assertSame('create', $this->row($result, 2)['action']);
        $this->assertStringContainsString('digit terakhirnya hilang', $this->row($result, 3)['errors'][0]);
        $this->assertSame('invalid', $this->row($result, 4)['action']);
        $this->assertStringContainsString('16 digit', $this->row($result, 5)['warnings'][0]);
        $this->assertSame('same', $this->row($result, 6)['action']);
        $this->assertCount(2, $this->row($result, 7)['errors']);
    }

    // ------------------------------------------------------------------
    // commit
    // ------------------------------------------------------------------

    public function testCommitUpsertsWithoutDuplicatesAndKeepsStatus(): void
    {
        $importer = new StudentImporter($this->db);
        $result   = $this->students([
            [1, '0012345678', 'Siswa Baru', 'L', '9C', 7, '01032013'],
            [2, '0000000012', 'Larasati Putri', 'P', '9B', 3, '08032011'],     // nonaktif, absen berubah
            [3, '0000000001', 'Ahmad Fauzan', 'L', '7A', 1, '05062013'],       // sama
            [4, '12', 'Salah', 'L', '7A', 1, '01032013'],                       // invalid
        ]);

        $this->assertStringContainsString('berstatus nonaktif', $this->row($result, 3)['warnings'][0]);

        $counts = $importer->commit($result['rows']);

        $this->assertSame(['created' => 1, 'updated' => 1, 'unchanged' => 1, 'skipped' => 1], $counts);
        $this->seeInDatabase('students', ['nisn' => '0012345678', 'name' => 'Siswa Baru', 'kelas' => '9C', 'status_aktif' => 1]);
        $this->seeInDatabase('students', ['nisn' => '0000000012', 'nomor_absen' => 3, 'status_aktif' => 0]);
        $this->assertSame(13, $this->db->table('students')->countAllResults());

        // Impor ulang file yang sama: tidak ada baris ganda.
        $again = $importer->commit($this->students([
            [1, '0012345678', 'Siswa Baru', 'L', '9C', 7, '01032013'],
        ])['rows']);
        $this->assertSame(['created' => 0, 'updated' => 0, 'unchanged' => 1, 'skipped' => 0], $again);
        $this->assertSame(13, $this->db->table('students')->countAllResults());
    }

    public function testCommitDecidesAgainstCurrentDatabaseState(): void
    {
        $importer = new TeacherImporter($this->db);
        $preview  = $this->teachers([[1, '198501012010011001', 'Guru Baru', '01011985']]);
        $this->assertSame('create', $preview['rows'][0]['action']);

        // Admin lain menambahkan NIP yang sama setelah pratinjau dibuat.
        $this->db->table('teachers')->insert(['nip' => '198501012010011001', 'name' => 'Nama Lama', 'kodeunik' => '01011985', 'status_aktif' => 1]);

        $counts = $importer->commit($preview['rows']);

        $this->assertSame(['created' => 0, 'updated' => 1, 'unchanged' => 0, 'skipped' => 0], $counts);
        $this->assertSame(1, $this->db->table('teachers')->where('nip', '198501012010011001')->countAllResults());
        $this->seeInDatabase('teachers', ['nip' => '198501012010011001', 'name' => 'Guru Baru']);
    }

    public function testImportedStudentCanLogInWithLeadingZeroIdentity(): void
    {
        $importer = new StudentImporter($this->db);
        $importer->commit($this->students([[1, 12345678, 'Login Nol', 'P', '8C', 1, 1032013]])['rows']);

        $this->assertNotNull(model(\App\Models\StudentModel::class)->findForLogin('0012345678', '01032013'));
    }

    // ------------------------------------------------------------------
    // penyimpanan pratinjau
    // ------------------------------------------------------------------

    public function testPreviewStoreIsBoundToAdminTypeAndExpiry(): void
    {
        $dir   = sys_get_temp_dir() . '/import-store-' . bin2hex(random_bytes(4));
        $store = new ImportStore($dir);
        $token = $store->save(1, VoterType::Student, ['rows' => [['row' => 2]]]);

        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);
        $this->assertSame([['row' => 2]], $store->load($token, 1, VoterType::Student)['rows']);
        $this->assertNull($store->load($token, 2, VoterType::Student), 'admin lain');
        $this->assertNull($store->load($token, 1, VoterType::Teacher), 'jenis lain');
        $this->assertNull($store->load('../../app/Config/Database', 1, VoterType::Student));

        $path = $dir . '/' . $token . '.json';
        $data = json_decode(file_get_contents($path), true);
        $data['created_at'] = time() - ImportStore::TTL - 1;
        file_put_contents($path, json_encode($data));
        $this->assertNull($store->load($token, 1, VoterType::Student), 'kedaluwarsa');

        $store->delete($token);
        $this->assertFileDoesNotExist($path);
        @rmdir($dir);
    }
}
