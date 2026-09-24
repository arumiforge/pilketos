<?php

namespace App\Libraries;

use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * PDF hasil akhir (Stage 13) dengan dompdf.
 *
 * - Font brand Newsreader & Plus Jakarta Sans (OFL) dalam bentuk TTF statis
 *   di app/Fonts (dompdf tidak membaca woff2 variabel); metrik font disimpan
 *   dompdf di writable/cache/dompdf.
 * - Tanpa akses jaringan, PHP, maupun JavaScript di dalam dokumen. Gambar
 *   (logo, foto pasangan) disisipkan sebagai data URI yang sudah diperkecil
 *   dan diubah ke JPEG/PNG lewat GD, jadi dompdf tidak membaca file di luar
 *   folder yang diizinkan.
 * - Nomor halaman ("Halaman X dari Y") digambar di setiap halaman setelah
 *   render; catatan kaki dokumen (elemen fixed) juga berulang di tiap halaman.
 */
final class ResultPdf
{
    /**
     * Keluarga font => [ [weight, style, file] ].
     */
    private const FONTS = [
        'Newsreader' => [
            ['normal', 'normal', 'Newsreader-Regular.ttf'],
            ['bold', 'normal', 'Newsreader-SemiBold.ttf'],
        ],
        'PlusJakartaSans' => [
            ['normal', 'normal', 'PlusJakartaSans-Regular.ttf'],
            ['bold', 'normal', 'PlusJakartaSans-Bold.ttf'],
            ['normal', 'italic', 'PlusJakartaSans-Italic.ttf'],
        ],
    ];

    /**
     * Jarak nomor halaman dari tepi bawah kertas (pt), di bawah catatan kaki
     * (admin/results/pdf: margin bawah @page 26 mm, catatan kaki -16 mm).
     */
    private const PAGE_NUMBER_BOTTOM = 12.0;

    public function render(string $html): string
    {
        $cache = WRITEPATH . 'cache' . DIRECTORY_SEPARATOR . 'dompdf';
        if (! is_dir($cache)) {
            mkdir($cache, 0775, true);
        }

        $fontDir = APPPATH . 'Fonts';

        $options = new Options();
        $options->setChroot([$fontDir, $cache]);
        $options->setFontDir($cache);
        $options->setFontCache($cache);
        $options->setTempDir($cache);
        $options->setIsRemoteEnabled(false);
        $options->setIsPhpEnabled(false);
        $options->setIsJavascriptEnabled(false);
        $options->setIsFontSubsettingEnabled(true);
        $options->setDefaultFont('PlusJakartaSans');
        $options->setDefaultMediaType('print');
        $options->setDpi(96);

        $dompdf  = new Dompdf($options);
        $metrics = $dompdf->getFontMetrics();

        foreach (self::FONTS as $family => $variants) {
            foreach ($variants as [$weight, $style, $file]) {
                $metrics->registerFont(
                    ['family' => $family, 'weight' => $weight, 'style' => $style],
                    $fontDir . DIRECTORY_SEPARATOR . $file,
                );
            }
        }

        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $this->pageNumbers($dompdf);

        return (string) $dompdf->output();
    }

    /**
     * Gambar kecil/sedang sebagai data URI (JPEG, atau PNG bila transparan),
     * sisi terpanjang dibatasi $maxSide piksel. $ratio (lebar/tinggi) memotong
     * bagian tengah gambar lebih dulu, pengganti object-fit: cover yang tidak
     * didukung dompdf. Null bila file tidak ada atau tidak dapat dibaca GD
     * (mis. WebP di server tanpa dukungan WebP).
     */
    public static function imageData(?string $path, int $maxSide = 640, ?float $ratio = null): ?string
    {
        if ($path === null || ! is_file($path)) {
            return null;
        }

        $bytes = @file_get_contents($path);
        $image = $bytes === false ? false : @imagecreatefromstring($bytes);
        if ($image === false) {
            return null;
        }

        $width  = imagesx($image);
        $height = imagesy($image);

        if ($ratio !== null && $ratio > 0 && abs($width / $height - $ratio) > 0.01) {
            $cropW   = (int) min($width, round($height * $ratio));
            $cropH   = (int) min($height, round($width / $ratio));
            $cropped = imagecrop($image, [
                'x'      => intdiv($width - $cropW, 2),
                'y'      => intdiv($height - $cropH, 2),
                'width'  => $cropW,
                'height' => $cropH,
            ]);
            if ($cropped !== false) {
                imagedestroy($image);
                $image  = $cropped;
                $width  = $cropW;
                $height = $cropH;
            }
        }

        $scale  = min(1, $maxSide / max($width, $height));

        if ($scale < 1) {
            $resized = imagescale($image, max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale)), IMG_BICUBIC);
            if ($resized !== false) {
                imagedestroy($image);
                $image = $resized;
            }
        }

        $png = strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'png';

        ob_start();
        if ($png) {
            imagesavealpha($image, true);
            imagepng($image, null, 9);
        } else {
            imagejpeg($image, null, 85);
        }
        $data = (string) ob_get_clean();
        imagedestroy($image);

        return 'data:image/' . ($png ? 'png' : 'jpeg') . ';base64,' . base64_encode($data);
    }

    private function pageNumbers(Dompdf $dompdf): void
    {
        $canvas  = $dompdf->getCanvas();
        $metrics = $dompdf->getFontMetrics();
        $font    = $metrics->getFont('PlusJakartaSans', 'normal');
        $size    = 7.5;
        $text    = 'Halaman {PAGE_NUM} dari {PAGE_COUNT}';
        // lebar perkiraan untuk rata tengah (angka diganti saat halaman ditulis)
        $width   = $metrics->getTextWidth('Halaman 9 dari 9', $font, $size);

        $canvas->page_text(
            ($canvas->get_width() - $width) / 2,
            $canvas->get_height() - self::PAGE_NUMBER_BOTTOM - $size,
            $text,
            $font,
            $size,
            [0.318, 0.306, 0.271],
        );
    }
}
