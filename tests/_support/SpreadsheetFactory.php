<?php

namespace Tests\Support;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * Pembuat file .xlsx untuk test import (Stage 3).
 *
 * Setiap sel boleh berupa:
 * - string  : sel teks (seperti kolom berformat Teks di template);
 * - int/float: sel angka (seperti Excel mengubah "0012345678" menjadi 12345678);
 * - ['number' => n, 'format' => '0000000000'] : angka dengan number format;
 * - ['date' => 'Y-m-d']: sel tanggal (Excel mengubah "01/03/2013" menjadi tanggal);
 * - null    : sel kosong.
 */
final class SpreadsheetFactory
{
    /**
     * @param list<list<mixed>> $rows Baris pertama biasanya header
     */
    public static function write(array $rows, ?string $path = null): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet       = $spreadsheet->getActiveSheet();

        foreach ($rows as $r => $row) {
            foreach (array_values($row) as $c => $value) {
                $coordinate = [$c + 1, $r + 1];

                if ($value === null) {
                    continue;
                }

                if (is_array($value) && isset($value['date'])) {
                    $sheet->setCellValue($coordinate, Date::PHPToExcel(new \DateTime($value['date'])));
                    $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
                } elseif (is_array($value) && isset($value['number'])) {
                    $sheet->setCellValueExplicit($coordinate, $value['number'], DataType::TYPE_NUMERIC);
                    $sheet->getStyle($coordinate)->getNumberFormat()->setFormatCode($value['format']);
                } elseif (is_int($value) || is_float($value)) {
                    $sheet->setCellValueExplicit($coordinate, $value, DataType::TYPE_NUMERIC);
                } else {
                    $sheet->setCellValueExplicit($coordinate, (string) $value, DataType::TYPE_STRING);
                }
            }
        }

        $path ??= tempnam(sys_get_temp_dir(), 'xlsx') . '.xlsx';
        (new Xlsx($spreadsheet))->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    /**
     * Header template siswa + baris data.
     *
     * @param list<list<mixed>> $rows
     */
    public static function students(array $rows): string
    {
        return self::write(array_merge([['no', 'NISN', 'nama', 'jenis_kelamin', 'kelas', 'nomor_absen', 'kodeunik']], $rows));
    }

    /**
     * Header template guru + baris data.
     *
     * @param list<list<mixed>> $rows
     */
    public static function teachers(array $rows): string
    {
        return self::write(array_merge([['no', 'NIP', 'nama', 'kodeunik']], $rows));
    }
}
