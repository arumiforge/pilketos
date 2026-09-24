<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Services\Import\ImportStore;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;
use Tests\Support\SpreadsheetFactory;
use Tests\Support\UploadFixture;

/**
 * Stage 4: hasil akhir final setelah pemilihan selesai.
 *
 * Saat FINISHED, tindakan yang mengubah angka/susunan hasil ditolak server:
 * status & hapus pemilih, impor, tambah/hapus pasangan, nomor urut & status
 * pasangan. Membuka kembali pemilihan lewat jadwal wajib konfirmasi eksplisit
 * dan tercatat di audit. Saat pemilihan masih berjalan semuanya tetap bisa.
 *
 * @internal
 */
final class ResultsLockTest extends CIUnitTestCase
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

        Services::resetSingle('throttler');
        cache()->clean();
        $this->storeDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'import-lock-' . bin2hex(random_bytes(4));
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
        Time::setTestNow();
        parent::tearDown();
    }

    private function admin(): array
    {
        return ['user_type' => 'admin', 'admin_id' => 1, 'isLoggedIn' => true];
    }

    private function postAdmin(string $path, array $data = [])
    {
        return $this->withSession($this->admin())->post($path, [csrf_token() => csrf_hash()] + $data);
    }

    private function finish(): void
    {
        $this->db->table('elections')->where('id', 1)->update(['start_at' => '2026-10-01 07:00:00', 'end_at' => '2026-10-01 12:00:00']);
        Time::setTestNow('2026-10-01 12:00:00');
    }

    private function studentFile(): string
    {
        $path          = SpreadsheetFactory::write([
            ['no', 'NISN', 'nama', 'jenis_kelamin', 'kelas', 'nomor_absen', 'kodeunik'],
            [1, '0098765432', 'Siswa Baru', 'L', '7A', 5, '02022013'],
        ]);
        $this->files[] = $path;

        return $path;
    }

    // ------------------------------------------------------------------

    public function testVoterStatusAndDeletionAreLockedAfterFinish(): void
    {
        $this->db->table('student_votes')->insert([
            'election_id' => 1, 'student_id' => 1, 'candidate_id' => 1, 'status' => 'LOCKED', 'voted_at' => '2026-10-01 08:00:00',
        ]);
        $this->finish();

        $this->postAdmin('admin/siswa/1/status', ['status_aktif' => '0'])->assertRedirectTo(site_url('admin/siswa/1'));
        $this->assertStringContainsString('hasil akhir', (string) session('error'));
        $this->seeInDatabase('students', ['id' => 1, 'status_aktif' => 1]);

        // Siswa tanpa riwayat pun tidak dapat dihapus: jumlah pemilih bagian dari hasil.
        $this->postAdmin('admin/siswa/4/hapus')->assertRedirectTo(site_url('admin/siswa/4'));
        $this->seeInDatabase('students', ['id' => 4]);

        $this->postAdmin('admin/guru/2/status', ['status_aktif' => '0']);
        $this->seeInDatabase('teachers', ['id' => 2, 'status_aktif' => 1]);

        $detail = $this->withSession($this->admin())->get('admin/siswa/1');
        $detail->assertSee('status akun dan data pemilih dikunci');
        $detail->assertDontSee('admin/siswa/1/status');

        $this->assertSame(0, $this->db->table('audit_logs')->countAllResults());
    }

    public function testVoterStatusStillWorksWhileOngoing(): void
    {
        Time::setTestNow('2026-10-01 09:00:00');
        $this->db->table('elections')->where('id', 1)->update(['start_at' => '2026-10-01 07:00:00', 'end_at' => '2026-10-01 12:00:00']);

        $this->postAdmin('admin/siswa/4/status', ['status_aktif' => '0']);

        $this->seeInDatabase('students', ['id' => 4, 'status_aktif' => 0]);
        $this->seeInDatabase('audit_logs', ['action' => 'STUDENT_STATUS']);
    }

    public function testImportIsLockedAfterFinish(): void
    {
        // Pratinjau dibuat saat pemilihan masih berjalan...
        Time::setTestNow('2026-10-01 11:00:00');
        $this->db->table('elections')->where('id', 1)->update(['start_at' => '2026-10-01 07:00:00', 'end_at' => '2026-10-01 12:00:00']);
        UploadFixture::attach(['file' => ['path' => $this->studentFile(), 'name' => 'siswa.xlsx']]);
        $upload = $this->postAdmin('admin/siswa/impor');
        $token  = substr($upload->response()->getHeaderLine('Location'), -32);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $token);

        // ...lalu pemilihan selesai sebelum admin menekan Impor.
        Time::setTestNow('2026-10-01 12:00:00');
        $this->postAdmin('admin/siswa/impor/simpan', ['token' => $token])->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('hasil akhir', (string) session('error'));
        $this->dontSeeInDatabase('students', ['nisn' => '0098765432']);

        UploadFixture::attach(['file' => ['path' => $this->studentFile(), 'name' => 'siswa.xlsx']]);
        $this->postAdmin('admin/siswa/impor')->assertRedirectTo(site_url('admin/siswa/impor'));
        $this->assertStringContainsString('hasil akhir', (string) session('error'));

        $page = $this->withSession($this->admin())->get('admin/siswa/impor');
        $page->assertSee('impor dikunci');
        $page->assertSee('admin/siswa/impor/templat'); // template tetap dapat diunduh
        $page->assertDontSee('enctype="multipart/form-data"');
    }

    public function testCandidateLineupIsLockedButTextEditsAreAllowedAfterFinish(): void
    {
        $this->finish();

        $this->withSession($this->admin())->get('admin/paslon/tambah')->assertRedirectTo(site_url('admin/paslon'));
        $this->postAdmin('admin/paslon', ['nomor_urut' => '4', 'nama_ketua' => 'Baru', 'nama_wakil' => 'Baru Juga', 'status_aktif' => '1'])
            ->assertRedirectTo(site_url('admin/paslon'));
        $this->assertSame(3, $this->db->table('candidates')->countAllResults());

        $this->postAdmin('admin/paslon/3/hapus')->assertRedirectTo(site_url('admin/paslon'));
        $this->seeInDatabase('candidates', ['id' => 3]);

        // Nomor urut & status dipertahankan; perbaikan nama tetap tersimpan.
        $this->postAdmin('admin/paslon/2', [
            'nomor_urut'   => '9',
            'nama_ketua'   => 'Bagas Prayoga, S.',
            'nama_wakil'   => 'Citra Maheswari',
            'status_aktif' => '0',
            'theme_accent' => '#2F5D50',
        ])->assertRedirectTo(site_url('admin/paslon'));

        $this->seeInDatabase('candidates', ['id' => 2, 'nomor_urut' => 2, 'status_aktif' => 1, 'nama_ketua' => 'Bagas Prayoga, S.']);

        $form = $this->withSession($this->admin())->get('admin/paslon/2/ubah');
        $form->assertSee('nomor urut dan status aktif dikunci');
        $form->assertSee('readonly');

        $index = $this->withSession($this->admin())->get('admin/paslon');
        $index->assertDontSee('admin/paslon/tambah');
        $index->assertSee('Dikunci: pemilihan sudah selesai');
    }

    public function testReopeningFinishedElectionRequiresExplicitConfirmation(): void
    {
        $this->finish();
        $form = [
            'nama'     => 'Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 DAWE',
            'tahun'    => '2026',
            'start_at' => '2026-10-01T07:00',
            'end_at'   => '2026-10-01T15:00',
        ];

        $page = $this->withSession($this->admin())->get('admin/jadwal');
        $page->assertSee('name="confirm_reopen"');

        $this->postAdmin('admin/jadwal', $form)->assertRedirectTo(site_url('admin/jadwal'));
        $this->assertArrayHasKey('confirm_reopen', (array) session('errors'));
        $this->seeInDatabase('elections', ['id' => 1, 'end_at' => '2026-10-01 12:00:00']);

        $this->postAdmin('admin/jadwal', $form + ['confirm_reopen' => '1'])->assertRedirectTo(site_url('admin/jadwal'));
        $this->seeInDatabase('elections', ['id' => 1, 'end_at' => '2026-10-01 15:00:00', 'status' => 'ONGOING']);

        $audit = $this->db->table('audit_logs')->where('action', 'SCHEDULE_UPDATE')->get()->getRowArray();
        $this->assertStringContainsString('Sudah Selesai -> Sedang Berlangsung', $audit['description']);

        // Setelah dibuka kembali, data dapat dikelola lagi.
        $this->postAdmin('admin/siswa/4/status', ['status_aktif' => '0']);
        $this->seeInDatabase('students', ['id' => 4, 'status_aktif' => 0]);
    }

    public function testEditingFinishedElectionWithoutReopeningNeedsNoConfirmation(): void
    {
        $this->finish();

        $this->postAdmin('admin/jadwal', [
            'nama'     => 'Pemilihan OSIS SMP 1 DAWE',
            'tahun'    => '2026',
            'start_at' => '2026-10-01T07:00',
            'end_at'   => '2026-10-01T12:00',
        ]);

        $this->seeInDatabase('elections', ['id' => 1, 'nama' => 'Pemilihan OSIS SMP 1 DAWE', 'status' => 'FINISHED']);
    }
}
