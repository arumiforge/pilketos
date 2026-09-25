<?php

namespace App\Services\Import;

use App\Services\VoterType;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

/**
 * Import guru: no, NIP, nama, kodeunik. Guru tetap entitas terpisah dari siswa.
 *
 * - NIP hanya angka (spasi di dalam NIP dibuang), maksimal 30 digit. NIP PNS
 *   18 digit; panjang lain (mis. NUPTK 16 digit) diterima dengan peringatan;
 * - NIP yang tersimpan sebagai angka > 15 digit DITOLAK karena Excel sudah
 *   membulatkan digit terakhirnya (tidak bisa dipulihkan);
 * - kodeunik tanggal lahir DDMMYYYY.
 */
final class TeacherImporter extends VoterImporter
{
    public const NIP_PNS_LENGTH = 18;
    public const NIP_MAX_LENGTH = 30;

    public function type(): VoterType
    {
        return VoterType::Teacher;
    }

    public function columns(): array
    {
        return [
            'no'       => ['field' => null, 'format' => NumberFormat::FORMAT_GENERAL, 'width' => 6, 'example' => '1', 'required' => false],
            'NIP'      => ['field' => 'nip', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 24, 'example' => '199305012020121004', 'required' => true],
            'nama'     => ['field' => 'name', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 36, 'example' => 'Fajar Afif Dewantoro, S.Pd.', 'required' => true],
            'kodeunik' => ['field' => 'kodeunik', 'format' => NumberFormat::FORMAT_TEXT, 'width' => 14, 'example' => '01012000', 'required' => true],
        ];
    }

    protected function instructions(): array
    {
        return [
            'Petunjuk impor data guru - Pemilihan Ketua OSIS SMP 1 DAWE 2026',
            '1. Isi data mulai baris 2 pada sheet pertama ("Data Guru"). Jangan mengubah, menghapus, atau memindahkan baris judul.',
            '2. NIP diketik sebagai teks (kolom sudah berformat Teks). NIP 18 digit yang diketik sebagai angka akan dibulatkan Excel dan ditolak sistem.',
            '3. Guru tanpa NIP PNS boleh memakai nomor identitas lain berupa angka (mis. NUPTK) yang juga dipakai untuk login.',
            '4. kodeunik = tanggal lahir DDMMYYYY sebagai teks, contoh 01012000 untuk 1 Januari 2000.',
            '5. NIP yang sudah ada akan DIPERBARUI (bukan dibuat ganda). Guru yang tidak ada di file tidak dihapus.',
            '6. Setelah unggah, periksa pratinjau: baris bermasalah ditandai dan tidak ikut diimpor.',
        ];
    }

    protected function checkFields(array $cells): array
    {
        $checks = [
            'nip'      => $this->identifier($cells['nip'], 'NIP', 0, self::NIP_MAX_LENGTH),
            'name'     => $this->name($cells['nama']),
            'kodeunik' => $this->kodeunik($cells['kodeunik']),
        ];

        [$nip, $nipErrors, $nipWarnings] = $checks['nip'];

        if ($nip !== null && strlen($nip) !== self::NIP_PNS_LENGTH) {
            $nipWarnings[] = sprintf('NIP terdiri dari %d digit (NIP PNS 18 digit). Pastikan nomor ini yang dipakai guru untuk login.', strlen($nip));
            $checks['nip'] = [$nip, $nipErrors, $nipWarnings];
        }

        return $checks;
    }
}
