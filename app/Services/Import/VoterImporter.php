<?php

namespace App\Services\Import;

use App\Services\VoterType;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Database;
use DateTimeImmutable;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Cell\DataValidation;
use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx as XlsxReader;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx as XlsxWriter;
use Throwable;
use ZipArchive;

/**
 * Import data pemilih dari Excel (.xlsx) dengan PhpSpreadsheet (Stage 3).
 *
 * Alur: template -> unggah -> parse() (baca + validasi + bandingkan dengan
 * database) -> pratinjau -> commit() (upsert dalam satu transaction).
 *
 * Aturan umum:
 * - hanya sheet pertama yang dibaca; baris header dicari di 5 baris teratas;
 * - identitas (NISN/NIP) & kode unik diperlakukan sebagai TEKS; kerusakan
 *   akibat Excel dipulihkan bila pasti (leading zero, tanggal) dan selalu
 *   dilaporkan sebagai peringatan di pratinjau, atau ditolak bila tidak pasti;
 * - identitas ganda di dalam file = error pada semua baris yang sama;
 * - identitas yang sudah ada di database = DIPERBARUI (upsert), bukan duplikat;
 *   status_aktif pemilih lama tidak diubah import;
 * - baris bermasalah tidak pernah diimpor; baris lain tetap bisa diimpor.
 */
abstract class VoterImporter
{
    public const MAX_ROWS   = 3000;
    public const MAX_BYTES  = 5 * 1024 * 1024;
    public const EXTENSIONS = ['xlsx'];

    /**
     * MIME hasil finfo untuk .xlsx berbeda antar versi libmagic.
     */
    public const MIME_TYPES = [
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/zip',
        'application/x-zip-compressed',
        'application/octet-stream',
    ];

    /**
     * Batas isi zip setelah diekstrak (anti "zip bomb").
     */
    private const MAX_UNZIPPED_BYTES = 60 * 1024 * 1024;
    private const MAX_ZIP_ENTRIES    = 200;

    private const HEADER_SCAN_ROWS = 5;
    private const MAX_COLUMNS      = 26;
    private const BATCH            = 200;

    public const ACTION_CREATE  = 'create';
    public const ACTION_UPDATE  = 'update';
    public const ACTION_SAME    = 'same';
    public const ACTION_INVALID = 'invalid';

    protected BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    abstract public function type(): VoterType;

    /**
     * Kolom template berurutan: header => [field database|null, format, lebar, contoh].
     *
     * @return array<string, array{field: string|null, format: string, width: float, example: string, required: bool}>
     */
    abstract public function columns(): array;

    /**
     * Validasi & normalisasi satu baris.
     *
     * @param array<string, ImportCell> $cells key = header baku (lihat headerKey())
     *
     * @return array{values: array<string, int|string|null>, errors: list<string>, warnings: list<string>}
     */
    abstract protected function validateRow(array $cells): array;

    /**
     * Petunjuk pengisian untuk sheet kedua template.
     *
     * @return list<string>
     */
    abstract protected function instructions(): array;

    public function templateFilename(): string
    {
        return $this->type()->value . '-import-template.xlsx';
    }

    /**
     * Kolom database yang diisi import (selain identitas), untuk perbandingan & update.
     *
     * @return list<string>
     */
    public function dataFields(): array
    {
        $fields = [];

        foreach ($this->columns() as $column) {
            if ($column['field'] !== null && $column['field'] !== $this->type()->identifierColumn()) {
                $fields[] = $column['field'];
            }
        }

        return $fields;
    }

    // ------------------------------------------------------------------
    // Template
    // ------------------------------------------------------------------

    /**
     * Isi file template .xlsx (sheet 1 = data, hanya header; sheet 2 = petunjuk).
     */
    public function templateBinary(): string
    {
        $spreadsheet = $this->templateSpreadsheet();
        $path        = tempnam(sys_get_temp_dir(), 'tpl');

        try {
            (new XlsxWriter($spreadsheet))->save($path);

            return (string) file_get_contents($path);
        } finally {
            @unlink($path);
            $spreadsheet->disconnectWorksheets();
        }
    }

    public function templateSpreadsheet(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator('Panitia Pemilihan OSIS SMP 1 Dawe')
            ->setTitle('Template impor ' . strtolower($this->type()->label()))
            ->setDescription('Isi data mulai baris 2 pada sheet pertama. Jangan ubah baris judul.');

        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Data ' . $this->type()->label());

        $index = 1;
        foreach ($this->columns() as $header => $column) {
            $letter = Coordinate::stringFromColumnIndex($index);
            $sheet->setCellValueExplicit($letter . '1', $header, 'str');
            $sheet->getColumnDimension($letter)->setWidth($column['width']);
            // Format seluruh kolom (bukan per sel) agar ketikan identitas &
            // kode unik tetap teks: leading zero tidak hilang.
            $sheet->getStyle($letter . ':' . $letter)->getNumberFormat()->setFormatCode($column['format']);
            $index++;
        }

        $lastLetter = Coordinate::stringFromColumnIndex(count($this->columns()));
        $headerRange = 'A1:' . $lastLetter . '1';
        $sheet->getStyle($headerRange)->getFont()->setBold(true);
        $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E3E0D8');
        $sheet->getStyle($headerRange)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM);
        $sheet->getStyle($headerRange)->getNumberFormat()->setFormatCode(NumberFormat::FORMAT_TEXT);
        $sheet->freezePane('A2');

        $this->addTemplateValidations($sheet);
        $sheet->setSelectedCell('A2');

        $help = $spreadsheet->createSheet();
        $help->setTitle('Petunjuk');
        $help->getColumnDimension('A')->setWidth(110);
        $row = 1;

        foreach ($this->instructions() as $line) {
            $help->setCellValueExplicit('A' . $row, $line, 'str');
            $help->getStyle('A' . $row)->getAlignment()->setWrapText(true)->setVertical(Alignment::VERTICAL_TOP);
            $row++;
        }

        $help->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $row++;
        $help->setCellValueExplicit('A' . $row, 'Contoh isi (JANGAN disalin ke sheet data bila bukan data asli):', 'str');
        $help->getStyle('A' . $row)->getFont()->setBold(true);
        $row++;

        $col = 1;
        foreach ($this->columns() as $header => $column) {
            $help->setCellValueExplicit([$col, $row], $header, 'str');
            $help->setCellValueExplicit([$col, $row + 1], $column['example'], 'str');
            $col++;
        }

        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    /**
     * Validasi data Excel tambahan pada template (dropdown, dsb.).
     */
    protected function addTemplateValidations(Worksheet $sheet): void
    {
    }

    protected function listValidation(string $range, string $list, string $prompt): DataValidation
    {
        $validation = new DataValidation();
        $validation->setType(DataValidation::TYPE_LIST)
            ->setErrorStyle(DataValidation::STYLE_STOP)
            ->setAllowBlank(true)
            ->setShowDropDown(true)
            ->setShowErrorMessage(true)
            ->setShowInputMessage(true)
            ->setErrorTitle('Nilai tidak valid')
            ->setError($prompt)
            ->setPromptTitle('Pilih salah satu')
            ->setPrompt($prompt)
            ->setFormula1('"' . $list . '"')
            ->setSqref($range);

        return $validation;
    }

    // ------------------------------------------------------------------
    // Membaca file
    // ------------------------------------------------------------------

    /**
     * Baca, validasi, dan bandingkan isi file dengan database. Tidak menulis apa pun.
     *
     * @return array{
     *     errors: list<string>,
     *     notices: list<string>,
     *     rows: list<array<string, mixed>>,
     *     summary: array<string, int>
     * }
     */
    public function parse(string $path): array
    {
        $result = ['errors' => [], 'notices' => [], 'rows' => [], 'summary' => self::emptySummary()];

        $zipError = $this->checkZip($path);
        if ($zipError !== null) {
            $result['errors'][] = $zipError;

            return $result;
        }

        try {
            $reader = new XlsxReader();

            if (! $reader->canRead($path)) {
                $result['errors'][] = 'File bukan workbook Excel .xlsx yang valid.';

                return $result;
            }

            $reader->setReadDataOnly(false)
                ->setReadEmptyCells(false)
                ->setIncludeCharts(false)
                ->setReadFilter($this->readFilter());

            $spreadsheet = $reader->load($path);
        } catch (Throwable $e) {
            log_message('warning', 'Import {type} gagal dibaca: {msg}', ['type' => $this->type()->value, 'msg' => $e->getMessage()]);
            $result['errors'][] = 'File tidak dapat dibaca. Pastikan file dibuat dari template dan disimpan sebagai .xlsx.';

            return $result;
        }

        try {
            $sheet = $spreadsheet->getSheet(0);
            $this->readSheet($sheet, $result);
        } finally {
            $spreadsheet->disconnectWorksheets();
        }

        if ($result['errors'] === []) {
            $this->compareWithDatabase($result['rows']);
            $result['summary'] = $this->summarize($result['rows']);
        }

        return $result;
    }

    /**
     * @param array{errors: list<string>, notices: list<string>, rows: list<array<string, mixed>>, summary: array<string, int>} $result
     */
    private function readSheet(Worksheet $sheet, array &$result): void
    {
        $highestRow = $sheet->getHighestDataRow();

        if ($highestRow < 1 || $this->sheetIsEmpty($sheet, $highestRow)) {
            $result['errors'][] = 'File kosong: sheet pertama tidak berisi data.';

            return;
        }

        [$headerRow, $map, $headerErrors, $notices] = $this->locateHeader($sheet, min($highestRow, self::HEADER_SCAN_ROWS));

        if ($headerErrors !== []) {
            $result['errors'] = $headerErrors;

            return;
        }

        $result['notices'] = $notices;
        $lastDataRow       = 0;

        for ($row = $headerRow + 1; $row <= $highestRow; $row++) {
            $cells = [];

            foreach ($map as $key => $columnIndex) {
                $cells[$key] = $sheet->cellExists([$columnIndex, $row])
                    ? ImportCell::fromCell($sheet->getCell([$columnIndex, $row]))
                    : ImportCell::empty();
            }

            if ($this->rowIsEmpty($cells)) {
                continue;
            }

            $lastDataRow = $row;
            $checked     = $this->validateRow($cells);

            $result['rows'][] = [
                'row'      => $row,
                'values'   => $checked['values'],
                'errors'   => $checked['errors'],
                'warnings' => $checked['warnings'],
                'action'   => $checked['errors'] === [] ? self::ACTION_CREATE : self::ACTION_INVALID,
                'changes'  => [],
                'inactive' => false,
            ];
        }

        if ($result['rows'] === []) {
            $result['errors'][] = 'Tidak ada baris data di bawah baris judul.';

            return;
        }

        // Baris di atas batas tidak dimuat (read filter): bila baris terakhir
        // yang dimuat masih berisi data, file melebihi batas.
        if (count($result['rows']) > self::MAX_ROWS || $lastDataRow >= self::readRowLimit()) {
            $result['rows']     = [];
            $result['errors'][] = sprintf(
                'File berisi lebih dari %s baris data. Pecah file menjadi beberapa bagian lalu impor satu per satu.',
                number_format(self::MAX_ROWS, 0, ',', '.'),
            );

            return;
        }

        $this->markDuplicates($result['rows']);
    }

    /**
     * Cari baris header di beberapa baris teratas (boleh ada baris judul di atasnya).
     *
     * @return array{0: int, 1: array<string, int>, 2: list<string>, 3: list<string>}
     */
    private function locateHeader(Worksheet $sheet, int $scanRows): array
    {
        $expected = [];
        foreach ($this->columns() as $header => $column) {
            $expected[self::headerKey($header)] = ['label' => $header, 'required' => $column['required']];
        }

        $bestErrors = null;

        for ($row = 1; $row <= $scanRows; $row++) {
            $map     = [];
            $unknown = [];
            $dupes   = [];

            for ($col = 1; $col <= self::MAX_COLUMNS; $col++) {
                if (! $sheet->cellExists([$col, $row])) {
                    continue;
                }

                $text = ImportCell::fromCell($sheet->getCell([$col, $row]))->text;
                if ($text === '') {
                    continue;
                }

                $key = self::headerKey($text);

                if (! isset($expected[$key])) {
                    $unknown[] = $text;
                } elseif (isset($map[$key])) {
                    $dupes[] = $expected[$key]['label'];
                } else {
                    $map[$key] = $col;
                }
            }

            if ($map === []) {
                continue;
            }

            $missing = [];
            foreach ($expected as $key => $info) {
                if ($info['required'] && ! isset($map[$key])) {
                    $missing[] = $info['label'];
                }
            }

            $errors = [];
            if ($missing !== []) {
                $errors[] = 'Header tidak sesuai template. Kolom wajib tidak ditemukan: ' . implode(', ', $missing)
                    . '. Header yang benar: ' . implode(', ', array_keys($this->columns())) . '.';
            }
            if ($dupes !== []) {
                $errors[] = 'Kolom header muncul lebih dari sekali: ' . implode(', ', array_unique($dupes)) . '.';
            }

            if ($errors === []) {
                $notices = $unknown === [] ? [] : ['Kolom tambahan diabaikan: ' . implode(', ', array_slice($unknown, 0, 10)) . '.'];

                return [$row, $map, [], $notices];
            }

            // Simpan kesalahan dari baris yang paling mirip header.
            if ($bestErrors === null || count($map) > $bestErrors[0]) {
                $bestErrors = [count($map), $errors];
            }
        }

        return [0, [], $bestErrors[1] ?? [
            'Header tidak ditemukan. Baris judul harus berisi: ' . implode(', ', array_keys($this->columns())) . '. Gunakan template yang disediakan.',
        ], []];
    }

    /**
     * Header dibandingkan tanpa beda huruf besar/kecil, spasi, garis bawah,
     * atau tanda baca: "Jenis Kelamin" = "jenis_kelamin", "Kode Unik" = "kodeunik".
     */
    public static function headerKey(string $header): string
    {
        return (string) preg_replace('/[^a-z0-9]/', '', strtolower(ImportCell::clean($header)));
    }

    /**
     * @param array<string, ImportCell> $cells
     */
    private function rowIsEmpty(array $cells): bool
    {
        foreach ($cells as $key => $cell) {
            // Kolom "no" saja (nomor urut yang sudah diisi sampai bawah) dianggap baris kosong.
            if ($key !== 'no' && ! $cell->isEmpty()) {
                return false;
            }
        }

        return true;
    }

    private function sheetIsEmpty(Worksheet $sheet, int $highestRow): bool
    {
        for ($row = 1; $row <= min($highestRow, self::MAX_ROWS + self::HEADER_SCAN_ROWS); $row++) {
            for ($col = 1; $col <= self::MAX_COLUMNS; $col++) {
                if ($sheet->cellExists([$col, $row]) && ! ImportCell::fromCell($sheet->getCell([$col, $row]))->isEmpty()) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Identitas yang muncul lebih dari sekali dalam file: semua barisnya ditolak.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function markDuplicates(array &$rows): void
    {
        $idColumn = $this->type()->identifierColumn();
        $seen     = [];

        foreach ($rows as $i => $row) {
            $id = $row['values'][$idColumn] ?? null;

            if (is_string($id) && $id !== '') {
                $seen[$id][] = $i;
            }
        }

        foreach ($seen as $id => $indexes) {
            if (count($indexes) < 2) {
                continue;
            }

            $lines = implode(', ', array_map(static fn (int $i): string => (string) $rows[$i]['row'], $indexes));

            foreach ($indexes as $i) {
                $rows[$i]['errors'][] = sprintf('%s %s ganda di file (baris %s).', $this->type()->identifierLabel(), $id, $lines);
                $rows[$i]['action']   = self::ACTION_INVALID;
            }
        }

        $this->markFileWarnings($rows);
    }

    /**
     * Peringatan lintas baris khusus jenis pemilih (mis. nomor absen ganda).
     *
     * @param list<array<string, mixed>> $rows
     */
    protected function markFileWarnings(array &$rows): void
    {
    }

    /**
     * Tentukan create/update/same berdasarkan data database saat ini.
     *
     * @param list<array<string, mixed>> $rows
     */
    private function compareWithDatabase(array &$rows): void
    {
        $idColumn = $this->type()->identifierColumn();
        $ids      = [];

        foreach ($rows as $row) {
            if ($row['action'] !== self::ACTION_INVALID) {
                $ids[] = $row['values'][$idColumn];
            }
        }

        $existing = $this->existingByIdentifier($ids);

        foreach ($rows as $i => $row) {
            if ($row['action'] === self::ACTION_INVALID) {
                continue;
            }

            $current = $existing[$row['values'][$idColumn]] ?? null;

            if ($current === null) {
                $rows[$i]['action'] = self::ACTION_CREATE;

                continue;
            }

            $changes = $this->diff($current, $row['values']);
            $rows[$i]['action']   = $changes === [] ? self::ACTION_SAME : self::ACTION_UPDATE;
            $rows[$i]['changes']  = $changes;
            $rows[$i]['inactive'] = (int) $current['status_aktif'] === 0;

            if ($rows[$i]['inactive']) {
                $rows[$i]['warnings'][] = sprintf(
                    '%s ini berstatus nonaktif; impor memperbarui data tetapi tidak mengaktifkannya kembali.',
                    $this->type()->label(),
                );
            }
        }
    }

    /**
     * @param list<string> $ids
     *
     * @return array<string, array<string, mixed>>
     */
    private function existingByIdentifier(array $ids): array
    {
        $idColumn = $this->type()->identifierColumn();
        $existing = [];

        foreach (array_chunk(array_values(array_unique($ids)), 500) as $chunk) {
            $rows = $this->db->table($this->type()->voterTable())
                ->select(implode(', ', array_merge(['id', $idColumn, 'status_aktif'], $this->dataFields())))
                ->whereIn($idColumn, $chunk)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $existing[(string) $row[$idColumn]] = $row;
            }
        }

        return $existing;
    }

    /**
     * Kolom yang berubah: field => [lama, baru].
     *
     * @return array<string, array{0: string|null, 1: string|null}>
     */
    private function diff(array $current, array $values): array
    {
        $changes = [];

        foreach ($this->dataFields() as $field) {
            $old = $current[$field] === null ? null : (string) $current[$field];
            $new = $values[$field] === null ? null : (string) $values[$field];

            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return array<string, int>
     */
    public static function summarize(array $rows): array
    {
        $summary = self::emptySummary();
        $summary['rows'] = count($rows);

        foreach ($rows as $row) {
            $summary[$row['action']]++;

            if ($row['warnings'] !== []) {
                $summary['warnings']++;
            }

            if ($row['warnings'] !== [] || $row['errors'] !== []) {
                $summary['issues']++;
            }
        }

        $summary['importable'] = $summary[self::ACTION_CREATE] + $summary[self::ACTION_UPDATE];

        return $summary;
    }

    /**
     * @return array<string, int>
     */
    private static function emptySummary(): array
    {
        return [
            'rows'               => 0,
            self::ACTION_CREATE  => 0,
            self::ACTION_UPDATE  => 0,
            self::ACTION_SAME    => 0,
            self::ACTION_INVALID => 0,
            'warnings'           => 0,
            'issues'             => 0,
            'importable'         => 0,
        ];
    }

    // ------------------------------------------------------------------
    // Commit
    // ------------------------------------------------------------------

    /**
     * Simpan baris valid dari pratinjau: identitas baru di-insert, identitas
     * lama di-update (upsert). Keputusan insert/update diulang terhadap isi
     * database SAAT commit, di dalam satu transaction.
     *
     * @param list<array<string, mixed>> $rows Baris hasil parse()
     *
     * @return array{created: int, updated: int, unchanged: int, skipped: int}
     *
     * @throws ImportConflictException bila data berubah bersamaan (unique key)
     */
    public function commit(array $rows, ?Time $now = null): array
    {
        $idColumn  = $this->type()->identifierColumn();
        $timestamp = ($now ?? Time::now())->toDateTimeString();
        $valid     = [];
        $skipped   = 0;

        foreach ($rows as $row) {
            if (($row['action'] ?? self::ACTION_INVALID) === self::ACTION_INVALID || ($row['errors'] ?? []) !== []) {
                $skipped++;

                continue;
            }

            $values = [];
            foreach (array_merge([$idColumn], $this->dataFields()) as $field) {
                $values[$field] = $row['values'][$field] ?? null;
            }

            $valid[(string) $values[$idColumn]] = $values;
        }

        $created = $updated = $unchanged = 0;
        $db      = $this->db;
        $previous = (bool) $db->transException;
        $db->transException(true);
        $db->transBegin();

        try {
            $existing = $this->existingByIdentifier(array_map('strval', array_keys($valid)));
            $inserts  = [];
            $updates  = [];

            foreach ($valid as $id => $values) {
                $current = $existing[(string) $id] ?? null;

                if ($current === null) {
                    $inserts[] = $values + ['status_aktif' => 1, 'created_at' => $timestamp, 'updated_at' => $timestamp];
                } elseif ($this->diff($current, $values) !== []) {
                    $updates[] = $values + ['updated_at' => $timestamp];
                } else {
                    $unchanged++;
                }
            }

            foreach (array_chunk($inserts, self::BATCH) as $chunk) {
                $db->table($this->type()->voterTable())->insertBatch($chunk);
            }

            foreach (array_chunk($updates, self::BATCH) as $chunk) {
                $db->table($this->type()->voterTable())->updateBatch($chunk, $idColumn);
            }

            $db->transCommit();
            $created = count($inserts);
            $updated = count($updates);
        } catch (DatabaseException $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }
            $db->resetTransStatus();

            if (in_array($e->getCode(), [1062, 1586, 1205, 1213], true)) {
                throw new ImportConflictException('Data berubah saat impor berlangsung.', 0, $e);
            }

            throw $e;
        } catch (Throwable $e) {
            if ($db->transDepth > 0) {
                $db->transRollback();
            }
            $db->resetTransStatus();

            throw $e;
        } finally {
            $db->transException($previous);
        }

        return ['created' => $created, 'updated' => $updated, 'unchanged' => $unchanged, 'skipped' => $skipped];
    }

    // ------------------------------------------------------------------
    // Validasi nilai bersama
    // ------------------------------------------------------------------

    /**
     * Identitas angka (NISN/NIP) sebagai teks.
     *
     * @param int $exactLength 0 = panjang bebas (1..$maxLength)
     *
     * @return array{0: string|null, 1: list<string>, 2: list<string>}
     */
    protected function identifier(ImportCell $cell, string $label, int $exactLength, int $maxLength): array
    {
        if ($cell->isEmpty()) {
            return [null, [$label . ' wajib diisi.'], []];
        }

        $warnings = [];

        if ($cell->numeric) {
            if ($cell->precisionLost) {
                return [null, [sprintf(
                    '%s tersimpan sebagai angka di Excel sehingga digit terakhirnya hilang (%s). Ubah format kolom %s menjadi Teks lalu ketik ulang.',
                    $label,
                    $cell->text,
                    $label,
                )], []];
            }

            // Format angka ber-nol ("0000000000") menyimpan tampilan yang benar.
            $digits = preg_match('/^\d+$/', $cell->formatted) === 1 && strlen($cell->formatted) >= strlen($cell->text)
                ? $cell->formatted
                : $cell->text;

            if (preg_match('/^\d+$/', $digits) !== 1) {
                return [null, [$label . ' hanya boleh berisi angka.'], []];
            }

            if ($exactLength > 0 && strlen($digits) < $exactLength) {
                $restored   = str_pad($digits, $exactLength, '0', STR_PAD_LEFT);
                $warnings[] = sprintf(
                    '%s tersimpan sebagai angka (%s); nol di depan dipulihkan menjadi %s. Pastikan benar.',
                    $label,
                    $digits,
                    $restored,
                );
                $digits = $restored;
            } elseif ($exactLength === 0) {
                $warnings[] = sprintf('%s tersimpan sebagai angka di Excel; pastikan tidak ada angka 0 di depan yang hilang.', $label);
            }
        } else {
            $digits = (string) preg_replace('/\s+/', '', $cell->text);

            if (preg_match('/^\d+$/', $digits) !== 1) {
                return [null, [$label . ' hanya boleh berisi angka (tanpa huruf, titik, atau strip).'], []];
            }
        }

        if ($exactLength > 0 && strlen($digits) !== $exactLength) {
            return [null, [sprintf('%s harus %d digit (terbaca %d digit).', $label, $exactLength, strlen($digits))], []];
        }

        if (strlen($digits) > $maxLength) {
            return [null, [sprintf('%s maksimal %d digit.', $label, $maxLength)], []];
        }

        return [$digits, [], $warnings];
    }

    /**
     * Kode unik = tanggal lahir DDMMYYYY sebagai teks.
     *
     * @return array{0: string|null, 1: list<string>, 2: list<string>}
     */
    protected function kodeunik(ImportCell $cell): array
    {
        if ($cell->isEmpty()) {
            return [null, ['Kode unik wajib diisi.'], []];
        }

        $warnings = [];

        if ($cell->date !== null) {
            // Excel mengubah "01/03/2013" menjadi tanggal: dikembalikan ke DDMMYYYY.
            $code = $cell->date->format('dmY');
        } elseif ($cell->numeric) {
            $code = $cell->text;

            if (preg_match('/^\d{7}$/', $code) === 1) {
                $restored   = '0' . $code;
                $warnings[] = sprintf('Kode unik tersimpan sebagai angka (%s); nol di depan dipulihkan menjadi %s.', $code, $restored);
                $code       = $restored;
            }
        } else {
            $code = (string) preg_replace('/[\s\-\/.]+/', '', $cell->text);
        }

        if (preg_match('/^\d{8}$/', $code) !== 1 || ! self::isBirthDate($code)) {
            return [null, ['Kode unik harus tanggal lahir DDMMYYYY yang valid, contoh 01032013.'], []];
        }

        return [$code, [], $warnings];
    }

    protected function name(ImportCell $cell): array
    {
        $name = $cell->text;

        if ($name === '') {
            return [null, ['Nama wajib diisi.'], []];
        }

        if (mb_strlen($name) > 150) {
            return [null, ['Nama maksimal 150 karakter.'], []];
        }

        return [$name, [], []];
    }

    private static function isBirthDate(string $code): bool
    {
        $day   = (int) substr($code, 0, 2);
        $month = (int) substr($code, 2, 2);
        $year  = (int) substr($code, 4, 4);

        if (! checkdate($month, $day, $year) || $year < 1900) {
            return false;
        }

        return DateTimeImmutable::createFromFormat('!dmY', $code) <= new DateTimeImmutable('today');
    }

    /**
     * Pastikan isi arsip wajar sebelum diekstrak PhpSpreadsheet.
     */
    private function checkZip(string $path): ?string
    {
        $zip = new ZipArchive();

        if ($zip->open($path) !== true) {
            return 'File bukan workbook Excel .xlsx yang valid.';
        }

        try {
            if ($zip->numFiles > self::MAX_ZIP_ENTRIES) {
                return 'Struktur file .xlsx tidak wajar (terlalu banyak bagian).';
            }

            $total = 0;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);
                $total += (int) ($stat['size'] ?? 0);
            }

            if ($total > self::MAX_UNZIPPED_BYTES) {
                return 'Isi file terlalu besar setelah diekstrak. Simpan ulang file dari template berisi data saja.';
            }
        } finally {
            $zip->close();
        }

        return null;
    }

    /**
     * Baris terakhir yang dimuat: header (maks. baris 5) + batas baris data + 1
     * baris penanda "masih ada data di bawahnya".
     */
    private static function readRowLimit(): int
    {
        return self::MAX_ROWS + self::HEADER_SCAN_ROWS + 1;
    }

    /**
     * Batasi sel yang dibaca: kolom A-Z dan baris sampai readRowLimit().
     */
    private function readFilter(): IReadFilter
    {
        return new class (self::MAX_COLUMNS, self::readRowLimit()) implements IReadFilter {
            public function __construct(private readonly int $maxColumn, private readonly int $maxRow)
            {
            }

            public function readCell(string $columnAddress, int $row, string $worksheetName = ''): bool
            {
                return $row <= $this->maxRow && Coordinate::columnIndexFromString($columnAddress) <= $this->maxColumn;
            }
        };
    }
}
