<?php

use App\Libraries\CandidateTheme;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Stage 2: tema visual per pasangan calon (warna, layout, asset, teks).
 *
 * @internal
 */
final class CandidateThemeTest extends CIUnitTestCase
{
    private function candidate(array $override = []): array
    {
        return $override + [
            'id'               => 7,
            'nomor_urut'       => 2,
            'nama_ketua'       => 'Bagas Prayoga',
            'nama_wakil'       => 'Citra Maheswari',
            'foto_ketua'       => null,
            'foto_wakil'       => null,
            'visi'             => ' Visi pasangan. ',
            'misi'             => "1. Satu\n2) Dua\n- Tiga\n\n  \n* Empat",
            'theme_name'       => 'Deep Forest',
            'theme_background' => null,
            'theme_accent'     => '#2f5d50',
            'theme_asset'      => null,
            'theme_layout'     => null,
            'status_aktif'     => 1,
        ];
    }

    public function testPresentsNumberNamesAndCleansMisi(): void
    {
        $theme = CandidateTheme::present($this->candidate());

        $this->assertSame('02', $theme['label']);
        $this->assertSame('Visi pasangan.', $theme['visi']);
        $this->assertSame(['Satu', 'Dua', 'Tiga', 'Empat'], $theme['misi']);
        $this->assertSame('BP', $theme['ketua_initials']);
        $this->assertSame('CM', $theme['wakil_initials']);
        $this->assertSame('Deep Forest', $theme['theme_name']);
    }

    public function testAccentIsValidatedAndGivesReadableInk(): void
    {
        $dark = CandidateTheme::present($this->candidate());
        $this->assertSame('#2F5D50', $dark['accent']);
        $this->assertSame('#FFFFFF', $dark['accent_ink']);
        $this->assertSame('#2F5D50', $dark['accent_text']);
        $this->assertSame('--accent: #2F5D50; --accent-ink: #FFFFFF; --accent-text: #2F5D50;', $dark['style']);

        // Kuning terang: teks di atasnya gelap, dan tidak dipakai sebagai warna teks di kertas.
        $light = CandidateTheme::present($this->candidate(['theme_accent' => '#F2D14B']));
        $this->assertSame('#15141A', $light['accent_ink']);
        $this->assertSame('#15141A', $light['accent_text']);

        // Nilai tidak valid / upaya injeksi CSS jatuh ke warna netral.
        foreach (['red', '#12', 'linear-gradient(red, blue)', '#FFF; background:url(x)', null] as $bad) {
            $this->assertSame('#15141A', CandidateTheme::present($this->candidate(['theme_accent' => $bad]))['accent']);
        }
    }

    public function testContrastRatio(): void
    {
        $this->assertEqualsWithDelta(21.0, CandidateTheme::contrast('#000000', '#FFFFFF'), 0.001);
        $this->assertEqualsWithDelta(1.0, CandidateTheme::contrast('#777777', '#777777'), 0.001);
    }

    public function testLayoutDiffersPerCandidateAndCanBeChosen(): void
    {
        $this->assertSame('split', CandidateTheme::present($this->candidate(['nomor_urut' => 1]))['layout']);
        $this->assertSame('poster', CandidateTheme::present($this->candidate(['nomor_urut' => 2]))['layout']);
        $this->assertSame('column', CandidateTheme::present($this->candidate(['nomor_urut' => 3]))['layout']);
        $this->assertSame('split', CandidateTheme::present($this->candidate(['nomor_urut' => 4]))['layout']);

        $chosen = CandidateTheme::present($this->candidate(['nomor_urut' => 1, 'theme_layout' => 'column']));
        $this->assertSame('column', $chosen['layout']);
        $this->assertSame('dots', $chosen['pattern']);

        $this->assertSame('poster', CandidateTheme::present($this->candidate(['theme_layout' => 'unknown']))['layout']);
    }

    public function testAssetUrlsComeFromUploadDirectoryOnly(): void
    {
        $theme = CandidateTheme::present($this->candidate([
            'foto_ketua'       => 'ketua.webp',
            'theme_background' => '../../app/Config/Database.php',
            'theme_asset'      => ['hero' => 'hero.webp', 'texture' => ['bukan-string'], 'poster' => 'poster a.jpg'],
        ]));

        $this->assertStringEndsWith('uploads/candidates/ketua.webp', $theme['photo_ketua']);
        $this->assertNull($theme['photo_wakil']);
        $this->assertStringEndsWith('uploads/candidates/Database.php', $theme['background']);
        $this->assertStringEndsWith('uploads/candidates/hero.webp', $theme['hero']);
        $this->assertNull($theme['texture']);
        $this->assertNull($theme['artwork']);
        $this->assertStringEndsWith('uploads/candidates/poster%20a.jpg', $theme['poster']);
    }

    public function testFallbacksForEmptyData(): void
    {
        $theme = CandidateTheme::present($this->candidate(['theme_name' => '', 'misi' => null, 'nama_wakil' => '']));

        $this->assertSame('Pasangan 02', $theme['theme_name']);
        $this->assertSame([], $theme['misi']);
        $this->assertSame('?', $theme['wakil_initials']);
    }
}
