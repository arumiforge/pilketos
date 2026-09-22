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
