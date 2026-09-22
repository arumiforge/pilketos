<?php

use CodeIgniter\I18n\Time;

/**
 * Helper tampilan bersama aplikasi (dimuat global via app/Config/Autoload.php).
 */
if (! function_exists('format_waktu')) {
    /**
     * Format DATETIME database (zona Asia/Jakarta) ke teks Indonesia,
     * contoh: "22 September 2026, 08.00 WIB".
     */
    function format_waktu(?string $datetime, string $pattern = 'd MMMM yyyy, HH.mm'): string
    {
        if ($datetime === null || $datetime === '') {
            return '-';
        }

        return Time::parse($datetime)->toLocalizedString($pattern) . ' WIB';
    }
}

if (! function_exists('election_status_label')) {
    /**
     * Label status election untuk UI. Status selalu disertai teks,
     * tidak hanya warna (aksesibilitas).
     */
    function election_status_label(?string $status): string
    {
        return match ($status) {
            'UPCOMING' => 'Belum Dibuka',
            'ONGOING'  => 'Sedang Berlangsung',
            'FINISHED' => 'Sudah Selesai',
            default    => 'Belum Dijadwalkan',
        };
    }
}

if (! function_exists('election_clock')) {
    /**
     * Jam server untuk countdown (milidetik epoch). Browser hanya menghitung
     * mundur dari selisih waktu server ini; status tetap diputuskan server.
     *
     * @return array{status: string|null, now: int, start: int|null, end: int|null}
     */
    function election_clock(?array $election): array
    {
        $clock = [
            'status' => $election['status'] ?? null,
            'now'    => Time::now()->getTimestamp() * 1000,
            'start'  => null,
            'end'    => null,
        ];

        if ($election !== null) {
            $clock['start'] = Time::parse($election['start_at'])->getTimestamp() * 1000;
            $clock['end']   = Time::parse($election['end_at'])->getTimestamp() * 1000;
        }

        return $clock;
    }
}

if (! function_exists('icon')) {
    /**
     * Ikon SVG inline (garis 1.75px, currentColor) agar gaya ikon konsisten
     * tanpa library ikon eksternal dan tanpa emoji. Dekoratif (aria-hidden);
     * makna selalu disampaikan juga lewat teks di sebelahnya.
     */
    function icon(string $name, string $class = ''): string
    {
        $paths = [
            'arrow-right' => '<path d="M5 12h14"/><path d="m13 6 6 6-6 6"/>',
            'arrow-down'  => '<path d="M12 5v14"/><path d="m6 13 6 6 6-6"/>',
            'lock'        => '<rect x="5" y="11" width="14" height="9" rx="1.5"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>',
            'check'       => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
            'alert'       => '<path d="M12 4 2.8 19.5h18.4Z"/><path d="M12 10v4.5"/><path d="M12 17.2v.3"/>',
            'clock'       => '<circle cx="12" cy="12" r="8.5"/><path d="M12 7.5V12l3 2"/>',
            'nail'        => '<path d="M6.5 4.5h11"/><path d="M9 4.5 10 7h4l1-2.5"/><path d="M11 7v10.5L12 21l1-3.5V7"/>',
            'layers'      => '<path d="m12 4 8.5 4.5L12 13 3.5 8.5Z"/><path d="m3.5 12.5 8.5 4.5 8.5-4.5"/>',
        ];

        if (! isset($paths[$name])) {
            return '';
        }

        $classAttr = trim('icon icon--' . $name . ' ' . $class);

        return '<svg class="' . esc($classAttr, 'attr') . '" viewBox="0 0 24 24" width="24" height="24" fill="none" '
            . 'stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round" '
            . 'aria-hidden="true" focusable="false">' . $paths[$name] . '</svg>';
    }
}
