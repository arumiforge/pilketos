<?php

namespace App\Libraries;

/**
 * Jenjang (7/8/9) dari nama kelas siswa SMP.
 *
 * Kelas ditulis bebas oleh sekolah, jadi jenjang diekstrak dengan aturan
 * sempit dan eksplisit, bukan menebak dari angka pertama di mana saja:
 * - angka Arab di awal: "7A", "7 B", "07-C", "8.1", "Kelas 9D";
 * - angka Romawi di awal: "VII A", "VIIIB", "IX-C", "kelas ix d".
 * Angka harus berdiri sendiri: "70", "10A", "VIIII", atau "IVA" tidak
 * dianggap jenjang 7-9. Kelas yang tidak dikenali masuk kelompok "Lainnya"
 * sehingga total rekap jenjang tetap sama dengan total siswa.
 */
final class Grade
{
    public const GRADES = [7, 8, 9];

    private const ROMAN = ['VII' => 7, 'VIII' => 8, 'IX' => 9];

    /**
     * Jenjang 7, 8, atau 9; null bila tidak dapat ditentukan.
     */
    public static function fromKelas(?string $kelas): ?int
    {
        $value = preg_replace('/^KELAS\s*/', '', self::normalizeKelas($kelas));

        if ($value === null || $value === '') {
            return null;
        }

        if (preg_match('/^0?([789])(?![0-9])/', $value, $match) === 1) {
            return (int) $match[1];
        }

        // Romawi terpanjang dicoba dulu (VIII sebelum VII); setelahnya tidak
        // boleh ada huruf romawi lain agar "VIIII" / "IXX" tidak lolos.
        if (preg_match('/^(VIII|VII|IX)(?![IVX])/', $value, $match) === 1) {
            return self::ROMAN[$match[1]];
        }

        return null;
    }

    /**
     * Bentuk baku kelas untuk disimpan: spasi dirapikan, huruf besar.
     * Contoh " 7a " menjadi "7A", "vii  b" menjadi "VII B".
     */
    public static function normalizeKelas(?string $kelas): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', (string) $kelas));

        return mb_strtoupper($value);
    }

    public static function label(?int $grade): string
    {
        return $grade === null ? 'Lainnya' : 'Kelas ' . $grade;
    }
}
