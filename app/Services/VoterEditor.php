<?php

namespace App\Services;

use App\Services\Import\StudentImporter;
use App\Services\Import\TeacherImporter;
use App\Services\Import\VoterImporter;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Database;

/**
 * Tambah & ubah satu siswa/guru langsung dari panel admin (Stage 11).
 *
 * Aturan isian sama persis dengan impor Excel (VoterImporter::validateForm):
 * NISN 10 digit / NIP angka, kelas berjenjang 7-9, kode unik = tanggal lahir
 * DDMMYYYY. Identitas (NISN/NIP) tidak boleh dipakai pemilih lain; nomor
 * absen ganda dalam satu kelas hanya peringatan (sama dengan impor).
 *
 * Status akun hanya diatur saat menambah (bawaan aktif); setelah itu lewat
 * tombol Aktifkan/Nonaktifkan di halaman detail (dengan konfirmasi).
 * Controller menolak penyimpanan setelah pemilihan selesai (hasil final).
 */
final class VoterEditor
{
    /**
     * Label kolom untuk pesan & audit.
     */
    private const LABELS = [
        'nisn'          => 'NISN',
        'nip'           => 'NIP',
        'name'          => 'nama',
        'jenis_kelamin' => 'jenis kelamin',
        'kelas'         => 'kelas',
        'nomor_absen'   => 'nomor absen',
        'kodeunik'      => 'kode unik',
    ];

    /**
     * Nilai kolom ini tidak ditulis ke audit log (dipakai untuk login).
     */
    private const SECRET = ['kodeunik'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    public function importer(VoterType $type): VoterImporter
    {
        return match ($type) {
            VoterType::Student => new StudentImporter($this->db),
            VoterType::Teacher => new TeacherImporter($this->db),
        };
    }

    /**
     * Kolom yang diisi lewat form, berurutan (identitas lebih dulu).
     *
     * @return list<string>
     */
    public function fields(VoterType $type): array
    {
        return array_values(array_filter(array_column($this->importer($type)->columns(), 'field')));
    }

    /**
     * Validasi lalu simpan. $existing null = pemilih baru.
     *
     * @param array<string, mixed> $input Body POST form
     *
     * @return array{
     *     ok: bool,
     *     id: int|null,
     *     values: array<string, int|string|null>,
     *     errors: array<string, string>,
     *     warnings: list<string>,
     *     changes: list<string>
     * }
     */
    public function save(VoterType $type, array $input, ?array $existing, ?Time $now = null): array
    {
        $checked  = $this->importer($type)->validateForm($input);
        $values   = $checked['values'];
        $errors   = $checked['errors'];
        $warnings = $checked['warnings'];
        $idColumn = $type->identifierColumn();
        $ownId    = $existing === null ? null : (int) $existing['id'];

        if (! isset($errors[$idColumn]) && ($owner = $this->identifierOwner($type, (string) $values[$idColumn], $ownId)) !== null) {
            $errors[$idColumn] = sprintf(
                '%s %s sudah dipakai %s%s. Satu %s hanya untuk satu pemilih.',
                $type->identifierLabel(),
                $values[$idColumn],
                $owner['name'],
                $type === VoterType::Student ? ' (kelas ' . $owner['kelas'] . ')' : '',
                $type->identifierLabel(),
            );
        }

        if ($errors !== []) {
            return self::result(false, null, $values, $errors, $warnings);
        }

        if ($type === VoterType::Student && $values['nomor_absen'] !== null) {
            array_push($warnings, ...$this->absenWarnings((string) $values['kelas'], (int) $values['nomor_absen'], $ownId));
        }

        $now   = ($now ?? Time::now())->toDateTimeString();
        $table = $this->db->table($type->voterTable());

        if ($existing === null) {
            $row = $values + [
                'status_aktif' => (string) ($input['status_aktif'] ?? '1') === '0' ? 0 : 1,
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

            try {
                $table->insert($row);
            } catch (DatabaseException) {
                $errors[$idColumn] = sprintf('%s %s gagal disimpan (mungkin baru saja dipakai pemilih lain). Coba lagi.', $type->identifierLabel(), $values[$idColumn]);

                return self::result(false, null, $values, $errors, $warnings);
            }

            return self::result(true, (int) $this->db->insertID(), $values, $errors, $warnings);
        }

        $changes = $this->changes($existing, $values);

        if ($changes !== []) {
            try {
                $table->where('id', $ownId)->update($values + ['updated_at' => $now]);
            } catch (DatabaseException) {
                $errors[$idColumn] = sprintf('%s %s gagal disimpan (mungkin baru saja dipakai pemilih lain). Coba lagi.', $type->identifierLabel(), $values[$idColumn]);

                return self::result(false, null, $values, $errors, $warnings);
            }
        }

        return self::result(true, $ownId, $values, $errors, $warnings, $changes);
    }

    private static function result(bool $ok, ?int $id, array $values, array $errors, array $warnings, array $changes = []): array
    {
        return [
            'ok'       => $ok,
            'id'       => $id,
            'values'   => $values,
            'errors'   => $errors,
            'warnings' => $warnings,
            'changes'  => $changes,
        ];
    }

    /**
     * Perubahan untuk audit, contoh "kelas 7A -> 7B", "kode unik".
     *
     * @return list<string>
     */
    private function changes(array $existing, array $values): array
    {
        $changes = [];

        foreach ($values as $field => $value) {
            $old = $existing[$field] ?? null;

            if ((string) ($old ?? '') === (string) ($value ?? '')) {
                continue;
            }

            $label     = self::LABELS[$field] ?? $field;
            $changes[] = in_array($field, self::SECRET, true)
                ? $label
                : sprintf('%s %s -> %s', $label, $old === null || $old === '' ? '(kosong)' : $old, $value === null || $value === '' ? '(kosong)' : $value);
        }

        return $changes;
    }

    private function identifierOwner(VoterType $type, string $identifier, ?int $exceptId): ?array
    {
        $builder = $this->db->table($type->voterTable())->where($type->identifierColumn(), $identifier);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        return $builder->get()->getRowArray();
    }

    /**
     * @return list<string>
     */
    private function absenWarnings(string $kelas, int $absen, ?int $exceptId): array
    {
        $builder = $this->db->table('students')
            ->select('name')
            ->where('kelas', $kelas)
            ->where('nomor_absen', $absen);

        if ($exceptId !== null) {
            $builder->where('id !=', $exceptId);
        }

        $names = array_column($builder->get()->getResultArray(), 'name');

        return $names === [] ? [] : [sprintf(
            'Nomor absen %d di kelas %s juga dipakai %s. Tetap disimpan; periksa kembali bila salah ketik.',
            $absen,
            $kelas,
            implode(', ', $names),
        )];
    }
}
