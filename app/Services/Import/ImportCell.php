<?php

namespace App\Services\Import;

use DateTimeInterface;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Throwable;

/**
 * Nilai satu sel Excel yang sudah dinormalisasi untuk import.
 *
 * Informasi tipe sel dipertahankan karena menentukan cara memulihkan
 * identitas yang rusak oleh Excel:
 * - sel TEKS: nilai apa adanya (leading zero aman);
 * - sel ANGKA: leading zero sudah hilang ("0012345678" menjadi 12345678),
 *   atau digit ke-16 dst. dibulatkan (NIP 18 digit);
 * - sel TANGGAL: kode unik "01/03/2013" yang diubah Excel menjadi tanggal.
 * Rumus tidak pernah dihitung ulang: yang dipakai hanya nilai tersimpan di file.
 */
final class ImportCell
{
    /**
     * Excel hanya menyimpan 15 digit signifikan untuk angka.
     */
    public const EXCEL_DIGITS = 15;

    public function __construct(
        public readonly string $text,
        public readonly bool $numeric = false,
        public readonly ?DateTimeInterface $date = null,
        public readonly string $formatted = '',
        public readonly bool $precisionLost = false,
    ) {
    }

    public static function empty(): self
    {
        return new self('');
    }

    public static function fromCell(Cell $cell): self
    {
        $value = $cell->isFormula() ? $cell->getOldCalculatedValue() : $cell->getValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if ($value === null) {
            return self::empty();
        }

        if (is_bool($value)) {
            return new self($value ? 'TRUE' : 'FALSE');
        }

        if (is_int($value) || is_float($value)) {
            return self::fromNumber($cell, $value);
        }

        return new self(self::clean((string) $value));
    }

    public function isEmpty(): bool
    {
        return $this->text === '' && $this->date === null;
    }

    /**
     * Rapikan teks: spasi tak terlihat/NBSP, karakter kontrol, dan spasi ganda.
     */
    public static function clean(string $value): string
    {
        $value = (string) preg_replace('/[\x{00A0}\x{2000}-\x{200A}\x{202F}\x{205F}\x{3000}]/u', ' ', $value);
        $value = (string) preg_replace('/[\x{200B}-\x{200D}\x{2060}\x{FEFF}]/u', '', $value);
        $value = (string) preg_replace('/[\x00-\x1F\x7F]+/u', ' ', $value);

        return trim((string) preg_replace('/ {2,}/', ' ', $value));
    }

    private static function fromNumber(Cell $cell, int|float $value): self
    {
        $formatCode = '';
        $date       = null;

        try {
            $formatCode = (string) $cell->getStyle()->getNumberFormat()->getFormatCode();

            if (Date::isDateTime($cell, $value)) {
                $date = Date::excelToDateTimeObject($value);
            }
        } catch (Throwable) {
            $date = null;
        }

        $integral = is_int($value) || (is_finite($value) && floor($value) === $value);
        $text     = $integral ? sprintf('%.0f', $value) : rtrim(rtrim(sprintf('%.6F', $value), '0'), '.');
        $digits   = strlen(ltrim($text, '-'));

        $formatted = '';
        if ($formatCode !== '' && $formatCode !== NumberFormat::FORMAT_GENERAL) {
            try {
                $formatted = self::clean(NumberFormat::toFormattedString($value, $formatCode));
            } catch (Throwable) {
                $formatted = '';
            }
        }

        return new self(
            $text,
            true,
            $date,
            $formatted,
            $integral && $digits > self::EXCEL_DIGITS,
        );
    }
}
