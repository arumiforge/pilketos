<?php

namespace App\Services\Import;

use App\Libraries\Grade;
use App\Services\VoterType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

/**
 * Import siswa: no, NISN, nama, jenis_kelamin, kelas, nomor_absen, kodeunik.
 *
 * - NISN tepat 10 digit (teks); angka tanpa nol depan dipulihkan + peringatan;
 * - jenis_kelamin L/P (juga "Laki-laki"/"Perempuan"), tidak ditebak dari nama;
 * - kelas dibakukan (huruf besar) dan wajib berjenjang 7, 8, atau 9;
 * - nomor_absen boleh kosong, 1-999;
 * - kodeunik tanggal lahir DDMMYYYY.
 */
final class StudentImporter extends VoterImporter
{
    public const NISN_LENGTH = 10;

    private const GENDERS = [
        'L'         => 'L',
        'LK'        => 'L',
        'LAKILAKI'  => 'L',
        'LAKI'      => 'L',
        'P'         => 'P',
        'PR'        => 'P',
        'PEREMPUAN' => 'P',
    ];

    public function type(): VoterType
    {
        return VoterType::Student;
    }

    public function columns(): array
    {
        return [
            'no'            => ['field' => null, 'format' => NumberFormat::FORMAT_GENERAL, 'width' => 6, 'example' => '1', 'required' => false],
            'NISN'          => ['field' => 'nisn', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 16, 'example' => '0012345678', 'required' => true],
            'nama'          => ['field' => 'name', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 32, 'example' => 'Ahmad Fauzan', 'required' => true],
            'jenis_kelamin' => ['field' => 'jenis_kelamin', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 14, 'example' => 'L', 'required' => true],
            'kelas'         => ['field' => 'kelas', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 10, 'example' => '7A', 'required' => true],
            'nomor_absen'   => ['field' => 'nomor_absen', 'format' => NumberFormat::FORMAT_NUMBER, 'width' => 13, 'example' => '1', 'required' => true],
            'kodeunik'      => ['field' => 'kodeunik', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 14, 'example' => '01032013', 'required' => true],
        ];
    }

    protected function addTemplateValidations(Worksheet $sheet): void
    {
        $sheet->setDataValidation('D2:D' . (self::MAX_ROWS + 1), $this->listValidation(
            'D2:D' . (self::MAX_ROWS + 1),
            'L,P',
            'Isi L (laki-laki) atau P (perempuan).',
        ));
    }

    protected function instructions(): array
    {
        return [
            'Petunjuk impor data siswa - Pemilihan Ketua OSIS SMP 1 DAWE 2026',
            '1. Isi data mulai baris 2 pada sheet pertama ("Data Siswa"). Jangan mengubah, menghapus, atau memindahkan baris judul.',
            '2. NISN wajib 10 digit dan diketik sebagai teks agar angka 0 di depan tidak hilang (kolom sudah berformat Teks).',
            '3. jenis_kelamin: L atau P. Jangan dikosongkan; sistem tidak menebak jenis kelamin dari nama.',
            '4. kelas diawali jenjang 7, 8, atau 9, contoh 7A, 8B, 9C, VII-A.',
            '5. nomor_absen boleh dikosongkan; bila diisi berupa angka 1-999.',
            '6. kodeunik = tanggal lahir DDMMYYYY sebagai teks, contoh 01032013 untuk 1 Maret 2013.',
            '7. NISN yang sudah ada akan DIPERBARUI (bukan dibuat ganda). Siswa yang tidak ada di file tidak dihapus.',
            '8. Setelah unggah, periksa pratinjau: baris bermasalah ditandai dan tidak ikut diimpor.',
        ];
    }

    protected function validateRow(array $cells): array
    {
        $values   = [];
        $errors   = [];
        $warnings = [];

        $collect = static function (string $field, array $checked) use (&$values, &$errors, &$warnings): void {
            [$value, $fieldErrors, $fieldWarnings] = $checked;
            $values[$field] = $value;
            array_push($errors, ...$fieldErrors);
            array_push($warnings, ...$fieldWarnings);
        };

        $collect('nisn', $this->identifier($cells['nisn'], 'NISN', self::NISN_LENGTH, self::NISN_LENGTH));
        $collect('name', $this->name($cells['nama']));
        $collect('jenis_kelamin', $this->gender($cells['jeniskelamin']));
        $collect('kelas', $this->kelas($cells['kelas']));
        $collect('nomor_absen', $this->absen($cells['nomorabsen']));
        $collect('kodeunik', $this->kodeunik($cells['kodeunik']));

        return ['values' => $values, 'errors' => $errors, 'warnings' => $warnings];
    }

    /**
     * Nomor absen ganda dalam satu kelas: kemungkinan salah ketik, tetap boleh diimpor.
     */
    protected function markFileWarnings(array &$rows): void
    {
        $seen = [];

        foreach ($rows as $i => $row) {
            $kelas = $row['values']['kelas'] ?? null;
            $absen = $row['values']['nomor_absen'] ?? null;

            if ($row['action'] !== self::ACTION_INVALID && $kelas !== null && $absen !== null) {
                $seen[$kelas . '#' . $absen][] = $i;
            }
        }

        foreach ($seen as $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $lines = implode(', ', array_map(static fn (int $i): string => (string) $rows[$i]['row'], $indexes));

            foreach ($indexes as $i) {
                $rows[$i]['warnings'][] = sprintf(
                    'Nomor absen %s di kelas %s dipakai lebih dari satu siswa (baris %s).',
                    $rows[$i]['values']['nomor_absen'],
                    $rows[$i]['values']['kelas'],
                    $lines,
                );
            }
        }
    }

    /**
     * @return array{0: string|null, 1: list<string>, 2: list<string>}
     */
    private function gender(ImportCell $cell): array
    {
        $key = (string) preg_replace('/[^A-Z]/', '', strtoupper($cell->text));

        if ($key === '') {
            return [null, ['Jenis kelamin wajib diisi (L atau P).'], []];
        }

        if (! isset(self::GENDERS[$key])) {
            return [null, ['Jenis kelamin harus L atau P (terbaca "' . mb_substr($cell->text, 0, 20) . '").'], []];
        }

        return [self::GENDERS[$key], [], []];
    }

    /**
     * @return array{0: string|null, 1: list<string>, 2: list<string>}
     */
    private function kelas(ImportCell $cell): array
    {
        $kelas = Grade::normalizeKelas($cell->text);

        if ($kelas === '') {
            return [null, ['Kelas wajib diisi.'], []];
        }

        if (mb_strlen($kelas) > 20) {
            return [null, ['Kelas maksimal 20 karakter.'], []];
        }

        if (preg_match('/^[A-Z0-9][A-Z0-9 .\/-]*$/', $kelas) !== 1) {
            return [null, ['Kelas hanya boleh berisi huruf, angka, spasi, titik, strip, atau garis miring.'], []];
        }

        if (Grade::fromKelas($kelas) === null) {
            return [null, ['Kelas harus diawali jenjang 7, 8, atau 9 (contoh 7A, 8B, IX-C); terbaca "' . $kelas . '".'], []];
        }

        return [$kelas, [], []];
    }

    /**
     * @return array{0: int|null, 1: list<string>, 2: list<string>}
     */
    private function absen(ImportCell $cell): array
    {
        if ($cell->isEmpty()) {
            return [null, [], []];
        }

        $text = trim($cell->text);

        if (preg_match('/^\d{1,3}$/', $text) !== 1 || (int) $text < 1) {
            return [null, ['Nomor absen harus angka bulat 1-999 atau dikosongkan.'], []];
        }

        return [(int) $text, [], []];
    }
}
