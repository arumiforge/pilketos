<?php

use App\Database\Seeds\DatabaseSeeder;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;

/**
 * Stage 8 (STAGE8-NOTES.md): rapikan dasbor pemilih & bilik suara.
 *
 * - navigasi HP ikon saja; kepala dasbor rata tengah, identitas "a / b / c"
 *   miring tanpa label terlihat; countdown pindah ke jam melayang di bawah;
 * - pembuka bilik suara tanpa sapaan, journey timeline, countdown terminal,
 *   tiap bagian satu layar di HP;
 * - paku 3D selalu nyala (tanpa tombol), konfirmasi menunggu 3 detik;
 * - eyebrow bab tanpa teks kembar, modal konfirmasi rata tengah + latar buram.
 *
 * Gerak & tata letak diuji di browser (STAGE8-NOTES.md bagian 4); di sini
 * markup, hook, dan aturan CSS/JS yang menjadi kontraknya.
 *
 * @internal
 */
final class BoothRefinementTest extends CIUnitTestCase
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
        service('renderer')->resetData();
    }

    protected function tearDown(): void
    {
        Time::setTestNow();
        parent::tearDown();
    }

    private function student(int $id = 1): array
    {
        return ['user_type' => 'student', 'student_id' => $id, 'isLoggedIn' => true];
    }

    private function teacher(int $id = 1): array
    {
        return ['user_type' => 'teacher', 'teacher_id' => $id, 'isLoggedIn' => true];
    }

    private function scheduleAt(string $now): void
    {
        $this->db->table('elections')->where('id', 1)
            ->update(['start_at' => '2026-10-01 07:00:00', 'end_at' => '2026-10-01 12:00:00']);
        Time::setTestNow($now);
    }

    private function asset(string $path): string
    {
        return (string) file_get_contents(FCPATH . $path);
    }

    // ------------------------------------------------------------------
    // navigasi & dasbor
    // ------------------------------------------------------------------

    public function testNavActionsAreIconOnlyOnPhonesButStillNamed(): void
    {
        $css = $this->asset('assets/css/app.css');
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 719px\) \{\s*\.site-nav__action \{[^}]*width: 44px;[^}]*\}\s*\.site-nav__label \{[^}]*clip: rect\(0, 0, 0, 0\);/',
            $css,
        );

        $page = $this->withSession($this->student())->get('siswa');
        $page->assertSee('<span class="site-nav__label">Dasbor');
        $page->assertSee('<span class="site-nav__label">Keluar</span>');
    }

    public function testDashboardHeadCentresNameAndSlashSeparatedIdentity(): void
    {
        $page = $this->withSession($this->student(3))->get('siswa');
        $html = (string) $page->response()->getBody();

        $page->assertDontSee('<p class="eyebrow">Dasbor Siswa</p>');
        $page->assertSee('<h1 class="dash-head__name">Candra Setiawan</h1>');
        // Nilai langsung; label NISN / Kelas / Nomor Absen hanya untuk pembaca layar.
        foreach (['NISN', 'Kelas', 'Nomor Absen'] as $label) {
            $page->assertSee('<dt class="visually-hidden">' . $label . '</dt>');
        }
        $this->assertLessThan(strpos($html, 'class="dash-meta"'), strpos($html, 'class="dash-head__name"'));

        $teacher = $this->withSession($this->teacher())->get('guru');
        $teacher->assertDontSee('<p class="eyebrow">Dasbor Guru</p>');
        $teacher->assertSee('<dt class="visually-hidden">NIP</dt>');

        $css = $this->asset('assets/css/app.css');
        $this->assertMatchesRegularExpression('/\.dash-head \{[^}]*text-align: center;/', $css);
        $this->assertMatchesRegularExpression('/\.dash-meta \{[^}]*font-style: italic;/', $css);
        $this->assertStringContainsString(".dash-meta > div + div::before {\n  content: '/';", $css);
    }

    public function testCountdownFloatsAtTheBottomOfVoterDashboards(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');

        foreach (['siswa' => $this->student(), 'guru' => $this->teacher()] as $path => $session) {
            $page = $this->withSession($session)->get($path);
            $html = (string) $page->response()->getBody();

            $page->assertSee('class="float-clock float-clock--ongoing" aria-label="Sisa waktu pemilihan"');
            $page->assertSee('countdown countdown--terminal countdown--ongoing');
            $page->assertSee('Ditutup dalam');
            $page->assertSee('data-unit="hours">03<');
            $page->assertDontSee('data-unit="days"');
            // Satu countdown saja: tidak lagi di kolom "Jadwal pemilihan".
            $this->assertSame(1, substr_count($html, 'role="timer"'), $path);
            $this->assertLessThan(strpos($html, 'class="float-clock'), strpos($html, 'id="schedule-title"'), $path);
        }

        $this->scheduleAt('2026-09-30 08:00:00');
        $upcoming = $this->withSession($this->student())->get('siswa');
        $upcoming->assertSee('float-clock--upcoming');
        $upcoming->assertSee('Dibuka dalam');

        $this->scheduleAt('2026-10-02 00:00:00');
        $this->withSession($this->student())->get('siswa')->assertDontSee('float-clock');

        $css = $this->asset('assets/css/app.css');
        $this->assertMatchesRegularExpression('/\.float-clock \{[^}]*position: sticky;[^}]*bottom: calc\(/', $css);
    }

    // ------------------------------------------------------------------
    // pembuka bilik suara
    // ------------------------------------------------------------------

    public function testBoothIntroIsCentredJourneyWithTerminalCountdown(): void
    {
        $this->scheduleAt('2026-10-01 09:00:00');

        $page = $this->withSession($this->student())->get('siswa/coblos');
        $html = (string) $page->response()->getBody();

        $page->assertDontSee('Halo, ');
        $page->assertDontSee('Bilik suara siswa');
        $page->assertSee('Kenali, lalu <span class="booth-intro__verb">coblos</span>.');

        $page->assertSee('<ol class="journey" aria-label="Langkah memilih" data-journey>');
        $this->assertSame(3, substr_count($html, 'data-journey-step'));
        $this->assertSame(1, substr_count($html, 'aria-current="step"'));
        $page->assertSee('<a href="#sekilas" class="journey__item">');
        $page->assertSee('<a href="#surat-suara" class="journey__item">');
        foreach (['Kenali paslon', 'Coblos satu', 'Konfirmasi &amp; kunci'] as $step) {
            $page->assertSee('<span class="journey__title">' . $step);
        }

        $page->assertSee('<div class="term">');
        $page->assertSee('sisa-waktu --tutup');
        $page->assertSee('countdown countdown--terminal countdown--ongoing');
        $page->assertSee('Ditutup dalam');
        $this->assertLessThan(strpos($html, 'class="lineup"'), strpos($html, 'class="term"'));

        $css = $this->asset('assets/css/voting.css');
        $this->assertMatchesRegularExpression('/\.booth-intro \{[^}]*text-align: center;/', $css);
        // HP: tiap bagian bilik suara setinggi satu layar.
        $this->assertMatchesRegularExpression(
            '/@media \(max-width: 719px\) \{\s*\.booth-intro \{[^}]*min-height: calc\(100svh - 65px\);[^}]*\}\s*\.lineup,\s*\.chapter,\s*\.ballot \{[^}]*min-height: 100svh;/',
            $css,
        );
        // Desktop: bagian lebih lebar dari kontainer bawaan (1080px).
        $this->assertStringContainsString(".booth-intro,\n.lineup,\n.chapter-nav,\n.chapter,\n.ballot { --container-w: 1360px; }", $css);

        $js = $this->asset('assets/js/candidates.js');
        $this->assertStringContainsString("document.addEventListener('osis:journey'", $js);
        $this->assertStringContainsString("steps[i].setAttribute('aria-current', 'step')", $js);
    }

    public function testClosedBoothKeepsJourneyWithoutPickLinkOrCountdown(): void
    {
        $this->scheduleAt('2026-10-02 00:00:00');

        $page = $this->withSession($this->student())->get('siswa/coblos');

        $page->assertSee('data-journey');
        $page->assertSee('Lihat surat suara dan kotak tiap pasangan.');
        $page->assertDontSee('class="term"');
        $page->assertSee('Pemilihan telah ditutup');
    }

    // ------------------------------------------------------------------
    // paku, bab, konfirmasi
    // ------------------------------------------------------------------

    public function testNailIsAlways3dAndConfirmationWaitsForThePunchedPaper(): void
    {
        $page = $this->withSession($this->student())->get('siswa/coblos');
        $page->assertDontSee('data-fx-toggle');
        $page->assertDontSee('Efek 3D');
        $page->assertSee('data-nail3d');
        $page->assertSee('data-nail2d');

        $js = $this->asset('assets/js/ballot.js');
        $this->assertStringContainsString('var CONFIRM_DELAY = 3000;', $js);
        $this->assertStringContainsString('}, CONFIRM_DELAY);', $js);
        $this->assertStringContainsString('window.NailWebGL.create(canvas, true)', $js);
        foreach (['fxToggle', 'data-fx-toggle', "App.getPref('fx')", 'lowEndDevice'] as $gone) {
            $this->assertStringNotContainsString($gone, $js, $gone);
        }

        $css = $this->asset('assets/css/voting.css');
        $this->assertStringNotContainsString('.fx-toggle', $css);
        $this->assertMatchesRegularExpression('/\.hole \{\s*--hole: 72px;/', $css);
        $this->assertStringContainsString('.hole { --hole: 92px; }', $css);
        $this->assertStringContainsString('.hole-bit {', $css);
    }

    public function testChapterEyebrowNeverRepeatsTheDefaultThemeName(): void
    {
        $this->db->table('candidates')->where('id', 1)->update(['theme_name' => '']);

        $page = $this->withSession($this->student())->get('siswa/coblos');
        $html = (string) $page->response()->getBody();

        $this->assertSame(1, substr_count($html, 'Pasangan <strong>01</strong>'));
        $this->assertStringNotContainsString('<span class="chapter__theme">Pasangan 01</span>', $html);
        $this->assertStringNotContainsString('<span>Pasangan 01</span>', $html);
        // Tema isian admin tetap tampil.
        $page->assertSee('<span class="chapter__theme">Deep Forest</span>');

        $css = $this->asset('assets/css/voting.css');
        $this->assertMatchesRegularExpression('/\.chapter__role \{[^}]*margin-bottom: var\(--space-3\);/', $css);
    }

    public function testConfirmDialogIsCentredOverABlurredBackdrop(): void
    {
        $page = $this->withSession($this->student())->get('siswa/coblos');
        $page->assertSee('id="vote-confirm"');
        $page->assertSee('class="confirm__warning" id="confirm-warning"');

        $css = $this->asset('assets/css/voting.css');
        $this->assertMatchesRegularExpression('/\.confirm \{[^}]*text-align: center;/', $css);
        $this->assertMatchesRegularExpression('/\.confirm::backdrop \{[^}]*backdrop-filter: blur\(10px\);/', $css);
        $this->assertMatchesRegularExpression('/\.confirm__warning \{[^}]*flex-direction: column;[^}]*align-items: center;/', $css);
    }
}
