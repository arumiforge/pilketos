<?php

namespace App\Libraries;

use App\Models\CandidateModel;

/**
 * Mengubah baris `candidates` menjadi data tampilan dengan identitas visual
 * per pasangan (MASTER section 6, Stage 2 "candidate-specific theme").
 *
 * Sumber tema (semua diisi admin di Stage 3):
 * - theme_accent     : warna solid #RRGGBB (tanpa gradient);
 * - theme_background : gambar latar panggung kandidat;
 * - theme_asset      : JSON {hero, texture, artwork, poster} berisi nama file;
 * - theme_layout     : art direction halaman (split | poster | column);
 * - foto_ketua / foto_wakil.
 * Asset yang belum diunggah tidak membuat halaman rusak: foto diganti monogram
 * inisial, texture diganti pola SVG solid sesuai layout.
 *
 * Semua URL dibuat lewat CandidateModel::assetUrl() (basename + rawurlencode),
 * dan warna aksen divalidasi ketat sehingga aman dipakai di atribut style.
 */
final class CandidateTheme
{
    public const LAYOUTS = ['split', 'poster', 'column'];

    /**
     * Pola bawaan per layout bila admin belum mengunggah texture.
     */
    public const PATTERNS = [
        'split'  => 'grid',
        'poster' => 'hatch',
        'column' => 'dots',
    ];

    public const ASSET_KEYS = ['hero', 'texture', 'artwork', 'poster'];

    private const INK      = '#15141A';
    private const PAPER    = '#FAF9F6';
    private const WHITE    = '#FFFFFF';
    private const FALLBACK = self::INK;

    /**
     * @return array<string, mixed>
     */
    public static function present(array $candidate): array
    {
        $number = (int) ($candidate['nomor_urut'] ?? 0);
        $accent = self::accent($candidate['theme_accent'] ?? null);
        $assets = is_array($candidate['theme_asset'] ?? null) ? $candidate['theme_asset'] : [];
        $layout = self::layout($candidate['theme_layout'] ?? null, $number);
        $ketua  = trim((string) ($candidate['nama_ketua'] ?? ''));
        $wakil  = trim((string) ($candidate['nama_wakil'] ?? ''));

        $theme = [
            'id'             => (int) ($candidate['id'] ?? 0),
            'number'         => $number,
            'label'          => sprintf('%02d', $number),
            'ketua'          => $ketua,
            'wakil'          => $wakil,
            'ketua_initials' => self::initials($ketua),
            'wakil_initials' => self::initials($wakil),
            'visi'           => trim((string) ($candidate['visi'] ?? '')),
            'misi'           => self::misiItems($candidate['misi'] ?? null),
            'theme_name'     => trim((string) ($candidate['theme_name'] ?? '')) ?: sprintf('Pasangan %02d', $number),
            'accent'         => $accent,
            'accent_ink'     => self::inkOn($accent),
            'accent_text'    => self::textOnPaper($accent),
            'layout'         => $layout,
            'pattern'        => self::PATTERNS[$layout],
            'photo_ketua'    => self::url($candidate['foto_ketua'] ?? null),
            'photo_wakil'    => self::url($candidate['foto_wakil'] ?? null),
            'background'     => self::url($candidate['theme_background'] ?? null),
        ];

        foreach (self::ASSET_KEYS as $key) {
            $theme[$key] = self::url($assets[$key] ?? null);
        }

        $theme['style'] = self::style($theme);

        return $theme;
    }

    /**
     * @param list<array<string, mixed>> $candidates
     *
     * @return list<array<string, mixed>>
     */
    public static function presentAll(array $candidates): array
    {
        return array_values(array_map(self::present(...), $candidates));
    }

    /**
     * Custom property CSS untuk elemen bertema kandidat. Nilai sudah tervalidasi
     * (#RRGGBB), sehingga aman ditaruh di atribut style.
     */
    public static function style(array $theme): string
    {
        return sprintf(
            '--accent: %s; --accent-ink: %s; --accent-text: %s;',
            $theme['accent'],
            $theme['accent_ink'],
            $theme['accent_text'],
        );
    }

    /**
     * Warna aksen solid tervalidasi, atau ink bila kosong/tidak valid.
     */
    public static function accent(mixed $value): string
    {
        if (is_string($value) && preg_match('/^#[0-9A-Fa-f]{6}$/', trim($value)) === 1) {
            return strtoupper(trim($value));
        }

        return self::FALLBACK;
    }

    /**
     * Warna teks di atas bidang aksen: putih atau ink, mana yang kontrasnya lebih tinggi.
     */
    public static function inkOn(string $accent): string
    {
        return self::contrast($accent, self::WHITE) >= self::contrast($accent, self::INK)
            ? self::WHITE
            : self::INK;
    }

    /**
     * Warna teks aksen di atas kertas: aksen bila kontras >= 3:1, selain itu ink.
     */
    public static function textOnPaper(string $accent): string
    {
        return self::contrast($accent, self::PAPER) >= 3.0 ? $accent : self::INK;
    }

    /**
     * Rasio kontras WCAG 2.x antara dua warna #RRGGBB.
     */
    public static function contrast(string $a, string $b): float
    {
        $la = self::luminance($a);
        $lb = self::luminance($b);

        return (max($la, $lb) + 0.05) / (min($la, $lb) + 0.05);
    }

    /**
     * Beda warna yang dirasakan mata (CIEDE2000) antara dua warna #RRGGBB:
     * 0 = identik, < ±2 hampir tidak terlihat, >= 20 jelas berbeda.
     * Dipakai aturan netralitas beranda (06-VISUAL-DIRECTION §5.1): warna
     * identitas sekolah tidak boleh mirip warna aksen pasangan mana pun.
     */
    public static function deltaE(string $a, string $b): float
    {
        return self::deltaE2000(self::lab($a), self::lab($b));
    }

    /**
     * CIEDE2000 (Sharma, Wu & Dalal 2005) antara dua warna CIELAB [L, a, b].
     *
     * @param array{0: float, 1: float, 2: float} $lab1
     * @param array{0: float, 1: float, 2: float} $lab2
     */
    public static function deltaE2000(array $lab1, array $lab2): float
    {
        [$l1, $a1, $b1] = $lab1;
        [$l2, $a2, $b2] = $lab2;

        $cBar = (hypot($a1, $b1) + hypot($a2, $b2)) / 2;
        $g    = 0.5 * (1 - sqrt($cBar ** 7 / ($cBar ** 7 + 25 ** 7)));
        $a1p  = (1 + $g) * $a1;
        $a2p  = (1 + $g) * $a2;
        $c1p  = hypot($a1p, $b1);
        $c2p  = hypot($a2p, $b2);
        $h1p  = $c1p == 0.0 ? 0.0 : fmod(rad2deg(atan2($b1, $a1p)) + 360, 360);
        $h2p  = $c2p == 0.0 ? 0.0 : fmod(rad2deg(atan2($b2, $a2p)) + 360, 360);

        $dLp = $l2 - $l1;
        $dCp = $c2p - $c1p;
        $dhp = 0.0;

        if ($c1p * $c2p != 0.0) {
            $dhp = $h2p - $h1p;
            $dhp += $dhp > 180 ? -360 : ($dhp < -180 ? 360 : 0);
        }

        $dHp = 2 * sqrt($c1p * $c2p) * sin(deg2rad($dhp / 2));

        $lBarP = ($l1 + $l2) / 2;
        $cBarP = ($c1p + $c2p) / 2;
        $hBarP = $h1p + $h2p;

        if ($c1p * $c2p != 0.0) {
            $hBarP = abs($h1p - $h2p) <= 180
                ? ($h1p + $h2p) / 2
                : (($h1p + $h2p < 360) ? ($h1p + $h2p + 360) / 2 : ($h1p + $h2p - 360) / 2);
        }

        $t = 1 - 0.17 * cos(deg2rad($hBarP - 30)) + 0.24 * cos(deg2rad(2 * $hBarP))
            + 0.32 * cos(deg2rad(3 * $hBarP + 6)) - 0.20 * cos(deg2rad(4 * $hBarP - 63));
        $dTheta = 30 * exp(-((($hBarP - 275) / 25) ** 2));
        $rc     = 2 * sqrt($cBarP ** 7 / ($cBarP ** 7 + 25 ** 7));
        $sl     = 1 + (0.015 * ($lBarP - 50) ** 2) / sqrt(20 + ($lBarP - 50) ** 2);
        $sc     = 1 + 0.045 * $cBarP;
        $sh     = 1 + 0.015 * $cBarP * $t;
        $rt     = -sin(deg2rad(2 * $dTheta)) * $rc;

        return sqrt(
            ($dLp / $sl) ** 2 + ($dCp / $sc) ** 2 + ($dHp / $sh) ** 2
            + $rt * ($dCp / $sc) * ($dHp / $sh),
        );
    }

    public static function layout(mixed $value, int $number): string
    {
        if (is_string($value) && in_array($value, self::LAYOUTS, true)) {
            return $value;
        }

        // Otomatis berbeda per nomor urut agar tiga pasangan tidak identik.
        return self::LAYOUTS[max(0, $number - 1) % count(self::LAYOUTS)];
    }

    /**
     * Pecah teks misi per baris dan buang penomoran manual ("1.", "2)", "-", "*").
     *
     * @return list<string>
     */
    public static function misiItems(mixed $misi): array
    {
        if (! is_string($misi) || trim($misi) === '') {
            return [];
        }

        $items = [];

        foreach (preg_split('/\R/u', $misi) as $line) {
            $line = trim((string) preg_replace('/^\s*(?:\d{1,2}\s*[.)]|[-*\x{2022}\x{2013}])\s*/u', '', $line));

            if ($line !== '') {
                $items[] = $line;
            }
        }

        return $items;
    }

    /**
     * Inisial dua kata pertama, contoh "Arka Wibisana" menjadi "AW".
     */
    public static function initials(string $name): string
    {
        $letters = '';

        foreach (preg_split('/\s+/u', trim($name)) as $word) {
            if (preg_match('/\p{L}/u', $word, $match) === 1) {
                $letters .= mb_strtoupper($match[0]);
            }

            if (mb_strlen($letters) === 2) {
                break;
            }
        }

        return $letters === '' ? '?' : $letters;
    }

    private static function url(mixed $filename): ?string
    {
        return is_string($filename) ? CandidateModel::assetUrl($filename) : null;
    }

    private static function luminance(string $hex): float
    {
        $channels = [];

        foreach ([1, 3, 5] as $offset) {
            $c          = hexdec(substr($hex, $offset, 2)) / 255;
            $channels[] = $c <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        return 0.2126 * $channels[0] + 0.7152 * $channels[1] + 0.0722 * $channels[2];
    }

    /**
     * #RRGGBB (sRGB) -> CIELAB [L, a, b] dengan titik putih D65.
     *
     * @return array{0: float, 1: float, 2: float}
     */
    private static function lab(string $hex): array
    {
        $rgb = [];

        foreach ([1, 3, 5] as $offset) {
            $c     = hexdec(substr($hex, $offset, 2)) / 255;
            $rgb[] = $c <= 0.04045 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        }

        [$r, $g, $b] = $rgb;
        $xyz         = [
            (0.4124564 * $r + 0.3575761 * $g + 0.1804375 * $b) / 0.95047,
            (0.2126729 * $r + 0.7151522 * $g + 0.0721750 * $b) / 1.0,
            (0.0193339 * $r + 0.1191920 * $g + 0.9503041 * $b) / 1.08883,
        ];
        $f = static fn (float $t): float => $t > 216 / 24389 ? $t ** (1 / 3) : (24389 / 27 * $t + 16) / 116;

        [$fx, $fy, $fz] = array_map($f, $xyz);

        return [116 * $fy - 16, 500 * ($fx - $fy), 200 * ($fy - $fz)];
    }
}
