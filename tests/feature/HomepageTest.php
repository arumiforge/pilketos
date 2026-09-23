<?php

use App\Database\Seeds\DatabaseSeeder;
use App\Models\ElectionModel;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Homepage;

/**
 * Redesign beranda (STAGE5-NOTES.md): navigasi logo di tengah tanpa tombol
 * masuk, tiga scene layar penuh, pintu masuk Siswa/Guru, layar pembuka,
 * panel status bergaya terminal, footer rata tengah, dan live count publik
 * (GET live-count) sebagai proyeksi sempit AnalyticsService.
 *
 * Perilaku gerak (scroll per scene, roda/trackpad, sentuh, keyboard,
 * transisi) diuji di browser; lihat STAGE5-NOTES.md bagian 9.
 *
 * @internal
 */
final class HomepageTest extends CIUnitTestCase
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
        // MockCache test tidak mengenal kedaluwarsa: angka dihitung setiap request.
        config(Homepage::class)->liveCacheSeconds = 0;
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    // ------------------------------------------------------------------
    // helper
    // ------------------------------------------------------------------

    private function scheduleAt(string $now, string $start = '2026-10-01 07:00:00', string $end = '2026-10-01 12:00:00'): void
    {
        $this->db->table('elections')->where('id', 1)->update(['start_at' => $start, 'end_at' => $end]);
        Time::setTestNow($now);
    }

    private function vote(string $type, int $voterId, int $candidateId, string $status = 'LOCKED'): void
    {
        $this->db->table($type . '_votes')->insert([
            'election_id'  => 1,
            $type . '_id'  => $voterId,
            'candidate_id' => $candidateId,
            'status'       => $status,
            'voted_at'     => '2026-10-01 08:00:00',
            'unlocked_at'  => $status === 'UNLOCKED' ? '2026-10-01 08:30:00' : null,
        ]);
    }

    /**
     * Suara sah: 01 = 1, 02 = 2, 03 = 1 (4 dari 15 pemilih aktif).
     * Tidak dihitung: riwayat UNLOCKED dan suara siswa nonaktif (id 12).
     */
    private function seedVotes(): void
    {
        $this->vote('student', 1, 2);
        $this->vote('student', 2, 2);
        $this->vote('student', 3, 1);
        $this->vote('teacher', 1, 3);
        $this->vote('student', 4, 1, 'UNLOCKED');
        $this->vote('student', 12, 1);
    }

    private function json($result): array
    {
        return json_decode($result->getJSON(), true);
    }

    // ------------------------------------------------------------------
    // navigasi & footer
    // ------------------------------------------------------------------

    public function testNavigationShowsTheCentredLogoWithoutLoginButtons(): void
    {
        $page = $this->get('/');
        $size = asset_size(config(Homepage::class)->logoOnDark);

        $page->assertStatus(200);
        $page->assertSee('site-nav site-nav--immersive');
        $page->assertSee('class="site-nav__brand" href="' . base_url('/') . '"');
        $page->assertSee('assets/img/brand/logo-light.svg');
        $page->assertSee('alt="Pemilihan Ketua OSIS SMP 1 DAWE 2026, ke beranda"');
        $page->assertSee('width="' . $size[0] . '" height="' . $size[1] . '"');
        $page->assertDontSee('Masuk Siswa');
        $page->assertDontSee('Masuk Guru');
        $page->assertDontSee('site-nav__action');

        // Halaman lain (latar terang): logo versi gelap, juga tanpa tombol masuk.
        // Penanda beranda imersif tidak terbawa ke render berikutnya (renderer bersama).
        $login = $this->get('student/login');
        $login->assertDontSee('is-immersive');
        $login->assertSee('class="site-footer"');
        $login->assertSee('assets/img/brand/logo-dark.svg');
        $login->assertDontSee('href="' . base_url('teacher/login') . '"');
        $login->assertDontSee('site-nav__action');
    }

    public function testSignedInVoterKeepsDashboardAndLogoutAroundTheLogo(): void
    {
        $page = $this->withSession(['user_type' => 'student', 'student_id' => 1, 'isLoggedIn' => true])
            ->get('student/dashboard');

        $page->assertStatus(200);
        $page->assertSee('aria-label="Navigasi akun"');
        $page->assertSee('href="' . base_url('student/dashboard') . '"');
        $page->assertSee('action="' . base_url('student/logout') . '"');
        $page->assertSee('Keluar');
        $page->assertSee('assets/img/brand/logo-dark.svg');
    }

    public function testFooterIsMinimalAndCentredOnEveryPage(): void
    {
        $login = $this->get('student/login');
        $body  = (string) $login->response()->getBody();

        $login->assertSee('class="site-footer"');
        $login->assertSee('SMP 1 DAWE');
        $this->assertSame(1, substr_count($body, '<footer'));

        // Beranda: footer ada di dalam scene terakhir (halaman tidak menggulir).
        $home = $this->get('/');
        $body = (string) $home->response()->getBody();

        $home->assertSee('site-footer site-footer--scene');
        $this->assertSame(1, substr_count($body, '<footer'));
        $this->assertLessThan(strpos($body, 'data-pager'), strpos($body, 'site-footer--scene'));
    }

    // ------------------------------------------------------------------
    // struktur beranda
    // ------------------------------------------------------------------

    public function testHomepageIsThreeFullScreenScenesWithSceneNavigation(): void
    {
        $page = $this->get('/');
        $body = (string) $page->response()->getBody();

        $page->assertSee('data-scenes');
        $this->assertSame(3, substr_count($body, 'class="scene scene--'));

        foreach (['beranda', 'masuk', 'perolehan'] as $id) {
            $page->assertSee('id="' . $id . '"');
            $page->assertSee('class="pager__link" href="#' . $id . '"');
        }

        $page->assertSee('aria-label="Bagian beranda"');
        $page->assertSee('href="#masuk"');
        $page->assertSee('Masuk untuk memilih');
        $page->assertSee('Lihat perolehan suara');

        // Mode imersif: layar penuh & safe-area HP, tema gelap, aset beranda.
        $page->assertSee('viewport-fit=cover');
        $page->assertSee('content="#0F0E13"');
        $page->assertSee('is-immersive');
        $page->assertSee('assets/css/home.css');
        $page->assertSee('assets/js/home.js');

        // Bagian lama "Sedang Berlangsung" (pita jam) & teaser tidak ada lagi di alur scene.
        $page->assertDontSee('clock-band');
        $page->assertDontSee('hero-x');
        $page->assertDontSee('teaser__');
    }

    public function testEntryPortalsKeepOnlyTheWhoWithReplaceableIllustrations(): void
    {
        $page = $this->get('/');

        $page->assertSee('aria-label="Masuk sebagai Siswa"');
        $page->assertSee('aria-label="Masuk sebagai Guru"');
        $page->assertSee('href="' . base_url('student/login') . '"');
        $page->assertSee('href="' . base_url('teacher/login') . '"');
        $page->assertSee('assets/img/home/entry-student.svg');
        $page->assertSee('assets/img/home/entry-teacher.svg');
        // Tanpa penjelasan "cara masuk" (NISN/NIP & kode unik ada di halaman login).
        $page->assertDontSee('NISN');
        $page->assertDontSee('kode unik');

        // Ilustrasi diganti lewat konfigurasi, termasuk versi HP opsional.
        $config                     = config(Homepage::class);
        $config->entryStudent       = 'assets/img/home/siswa.webp';
        $config->entryStudentMobile = 'assets/img/home/siswa-hp.webp';

        $page = $this->get('/');
        $page->assertSee('src="' . base_url('assets/img/home/siswa.webp') . '"');
        $page->assertSee('media="(orientation: portrait) and (max-width: 767px)" srcset="' . base_url('assets/img/home/siswa-hp.webp') . '"');
    }

    public function testSplashScreenHasSeparateLandscapeAndPortraitBackgrounds(): void
    {
        $config               = config(Homepage::class);
        $config->introDesktop = 'assets/img/home/pembuka-desktop.webp';
        $config->introMobile  = 'assets/img/home/pembuka-hp.webp';

        $page = $this->get('/');

        $page->assertSee('data-splash');
        $page->assertSee('media="(orientation: portrait)" srcset="' . base_url('assets/img/home/pembuka-hp.webp') . '"');
        $page->assertSee('src="' . base_url('assets/img/home/pembuka-desktop.webp') . '"');
        $page->assertSee('splash__shade');
        $page->assertSee('data-splash-logo');
        $page->assertSee('data-splash-fill');
        // Sekali per sesi tab: penanda dibaca skrip inline ber-nonce sebelum render.
        $page->assertSee("sessionStorage.getItem('osis2026.intro')");
    }

    public function testStatusDockReplacesTheOngoingSection(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');

        $page = $this->get('/');

        $page->assertSee('aria-label="Status pemilihan"');
        $page->assertSee('dock dock--ongoing');
        $page->assertSee('Sedang Berlangsung');
        $page->assertSee('countdown countdown--dock countdown--ongoing');
        $page->assertSee('Ditutup dalam');
        // Sisa 3 jam: 03:00:00 dirender server, tanpa kolom hari.
        $page->assertSee('data-unit="hours">03<');
        $page->assertDontSee('data-unit="days"');
        $page->assertSee('data-timeline-fill');
        $page->assertSee('pilketos:~$');
        $page->assertSee('datetime="2026-10-01 07:00:00"');
        $page->assertSee('datetime="2026-10-01 12:00:00"');

        // Lebih dari sehari sebelum dibuka: kolom hari tampil.
        $this->scheduleAt('2026-09-28 07:00:00');

        $upcoming = $this->get('/');
        $upcoming->assertSee('dock dock--upcoming');
        $upcoming->assertSee('Dibuka dalam');
        $upcoming->assertSee('data-unit="days">03<');
    }

    // ------------------------------------------------------------------
    // live count publik
    // ------------------------------------------------------------------

    public function testLiveCountJsonIsANarrowProjectionOfTheAnalytics(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');
        $this->seedVotes();

        $result = $this->get('live-count');

        $result->assertStatus(200);
        $this->assertStringContainsString('no-store', $result->response()->getHeaderLine('Cache-Control'));

        $data = $this->json($result);
        $this->assertSame(['status', 'label', 'candidates', 'turnout', 'updated_at', 'updated_label', 'poll'], array_keys($data));
        $this->assertSame('ONGOING', $data['status']);
        $this->assertSame('Sedang Berlangsung', $data['label']);
        $this->assertSame(['id', 'label', 'percent'], array_keys($data['candidates'][0]));
        $this->assertSame([1, 2, 3], array_column($data['candidates'], 'id'));
        $this->assertSame(['01', '02', '03'], array_column($data['candidates'], 'label'));
        // JSON menulis 25.0 sebagai 25: bandingkan nilainya.
        $this->assertEquals([25, 50, 25], array_column($data['candidates'], 'percent'));
        $this->assertSame(['voted', 'total', 'percent'], array_keys($data['turnout']));
        $this->assertSame(4, $data['turnout']['voted']);
        $this->assertSame(15, $data['turnout']['total']);
        $this->assertEqualsWithDelta(26.67, $data['turnout']['percent'], 0.001);
        $this->assertSame('1 Oktober 2026, 09.00.00 WIB', $data['updated_label']);
        $this->assertSame('2026-10-01T09:00:00+07:00', $data['updated_at']);
        $this->assertSame(['interval' => 30], $data['poll']);

        // Satu definisi angka dengan dasbor admin.
        $snapshot = service('analytics')->snapshot(model(ElectionModel::class)->getCurrentElection());
        $this->assertEquals(array_column($snapshot['candidates'], 'percent'), array_column($data['candidates'], 'percent'));
        $this->assertEquals($snapshot['summary']['all']['participation'], $data['turnout']['percent']);

        // Tanpa rincian admin maupun identitas: tidak ada jumlah suara per
        // pasangan, pemisahan siswa/guru, rekap kelas/jenjang/jenis kelamin, nama.
        $raw = $result->getJSON();
        foreach (['summary', 'groups', 'votes', 'student_votes', 'teacher_votes', 'kelas', 'grade', 'gender', 'ketua', 'wakil', 'name', 'nisn', 'nip'] as $key) {
            $this->assertStringNotContainsString('"' . $key . '"', $raw, $key);
        }
    }

    public function testHomepageRendersTheSameNumbersAsTheEndpoint(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');
        $this->seedVotes();

        $page = $this->get('/');

        $page->assertSee('data-live-url="' . site_url('live-count') . '"');
        $page->assertSee('data-live-status="ONGOING"');
        $page->assertSee('data-live-interval="30"');
        $page->assertSee('data-live-pair="1"');
        $page->assertSee('data-value="25.00">25,0<');
        $page->assertSee('data-value="50.00">50,0<');
        $page->assertSee('data-value="26.67">26,7<');
        $page->assertSee('pemilih sudah memilih');
        $page->assertSee('Suara masuk');
        $page->assertSee('Diperbarui');
        $page->assertSee('1 Oktober 2026, 09.00.00 WIB');
        $page->assertSee('Diperbarui otomatis tiap 30 detik.');
        // Foto/monogram pasangan adalah fokus visual; nama tetap terbaca.
        $page->assertSee('pair__photo');
        $page->assertSee('Arka Wibisana');
        $page->assertSee('Naya Kirana');
    }

    public function testLivePairsFollowTheCandidatesThatAreCounted(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');
        $this->vote('student', 1, 3);
        // 03 dinonaktifkan setelah menerima suara: tetap dihitung (total 100%).
        // 02 dinonaktifkan tanpa suara: tidak tampil.
        $this->db->table('candidates')->where('id', 3)->update(['status_aktif' => 0]);
        $this->db->table('candidates')->where('id', 2)->update(['status_aktif' => 0]);

        $page = $this->get('/');
        $page->assertSee('data-live-pair="1"');
        $page->assertDontSee('data-live-pair="2"');
        $page->assertSee('data-live-pair="3"');
        $page->assertSee('Dewi Anggraini');

        $data = $this->json($this->get('live-count'));
        $this->assertSame([1, 3], array_column($data['candidates'], 'id'));
        $this->assertEquals(100, $data['candidates'][1]['percent']);
    }

    public function testPollingFollowsTheElectionStatus(): void
    {
        $this->scheduleAt('2026-10-01 06:00:00');
        $upcoming = $this->json($this->get('live-count'));
        $this->assertSame('UPCOMING', $upcoming['status']);
        $this->assertSame(30, $upcoming['poll']['interval']);
        $this->get('/')->assertSee('Penghitungan dimulai saat pencoblosan dibuka.');

        // Tepat pada end_at: selesai, polling berhenti.
        $this->scheduleAt('2026-10-01 12:00:00');
        $finished = $this->json($this->get('live-count'));
        $this->assertSame('FINISHED', $finished['status']);
        $this->assertSame(0, $finished['poll']['interval']);

        $page = $this->get('/');
        $page->assertSee('Perolehan akhir');
        $page->assertSee('Hasil resmi diumumkan oleh panitia pemilihan OSIS.');
        $page->assertSee('data-live-interval="0"');
        $page->assertDontSee('Diperbarui otomatis');

        // Tanpa jadwal pemilihan: tanpa status, tanpa polling.
        $this->db->table('elections')->delete(['id' => 1]);
        $none = $this->json($this->get('live-count'));
        $this->assertNull($none['status']);
        $this->assertSame(0, $none['poll']['interval']);
        $this->assertSame(15, $none['turnout']['total']);

        $page = $this->get('/');
        $page->assertStatus(200);
        $page->assertSee('Belum Dijadwalkan');
        $page->assertSee('Jadwal belum tersedia');
        $page->assertSee('Jadwal pemilihan belum tersedia.');
    }

    public function testLiveCountIsCachedBrieflyPerElectionStatus(): void
    {
        config(Homepage::class)->liveCacheSeconds = 5;
        $this->scheduleAt('2026-10-01 09:00:00');

        $first = $this->json($this->get('live-count'));
        $this->vote('student', 1, 1);

        // Dalam masa cache: angka yang sama untuk semua layar.
        $this->assertSame($first, $this->json($this->get('live-count')));

        // Status berubah = kunci cache baru: tidak pernah basi.
        $this->scheduleAt('2026-10-01 12:00:00');
        $final = $this->json($this->get('live-count'));
        $this->assertSame('FINISHED', $final['status']);
        $this->assertEquals(100, $final['candidates'][0]['percent']);
    }

    public function testPublicLiveCountCanBeTurnedOffForTheCandidatesTeaser(): void
    {
        config(Homepage::class)->publicLiveCount = false;

        $page = $this->get('/');

        $page->assertStatus(200);
        $page->assertSee('id="pasangan"');
        $page->assertSee('class="pager__link" href="#pasangan"');
        $page->assertSee('Kenali pasangan calon');
        $page->assertSee('Arka Wibisana');
        $page->assertSee('Terracotta');
        $page->assertDontSee('data-live');
        $page->assertDontSee('Suara masuk');
        $page->assertDontSee('Perolehan suara');
    }

    public function testLiveCountEndpointIsGoneWhenTurnedOff(): void
    {
        config(Homepage::class)->publicLiveCount = false;

        $this->expectException(PageNotFoundException::class);

        $this->get('live-count');
    }
}
