<?php

namespace App\Libraries;

use App\Models\AdminModel;
use CodeIgniter\I18n\Time;
use InvalidArgumentException;

/**
 * Akun admin untuk production (Stage 4).
 *
 * DatabaseSeeder hanya untuk development (admin/admin123). Admin production
 * dibuat lewat `php spark admin:create`, dengan kata sandi ACAK yang dibuat
 * server dan ditampilkan sekali di terminal: tidak pernah diketik di baris
 * perintah (tidak tersimpan di riwayat terminal) dan tidak tersimpan dalam
 * bentuk asli (hanya password_hash()).
 */
final class AdminAccount
{
    public const USERNAME_PATTERN = '/^[a-z0-9][a-z0-9._-]{2,49}$/';
    public const PASSWORD_LENGTH  = 16;

    /**
     * Tanpa karakter yang mudah tertukar saat dibacakan/diketik (0/O, 1/l/I).
     */
    private const ALPHABET = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKLMNPQRSTUVWXYZ23456789';

    public static function generatePassword(int $length = self::PASSWORD_LENGTH): string
    {
        $password = '';
        $max      = strlen(self::ALPHABET) - 1;

        for ($i = 0; $i < $length; $i++) {
            $password .= self::ALPHABET[random_int(0, $max)];
        }

        return $password;
    }

    /**
     * @return array{id: int, username: string, password: string}
     *
     * @throws InvalidArgumentException pesan siap tampil
     */
    public function create(string $name, string $username): array
    {
        $name     = trim((string) preg_replace('/\s+/u', ' ', $name));
        $username = strtolower(trim($username));

        if ($name === '' || mb_strlen($name) > 150) {
            throw new InvalidArgumentException('Nama admin wajib diisi (maksimal 150 karakter).');
        }

        if (preg_match(self::USERNAME_PATTERN, $username) !== 1) {
            throw new InvalidArgumentException('Nama pengguna 3-50 karakter: huruf kecil, angka, titik, strip, atau garis bawah.');
        }

        $model = model(AdminModel::class);

        if ($model->where('username', $username)->countAllResults() > 0) {
            throw new InvalidArgumentException('Nama pengguna "' . $username . '" sudah dipakai.');
        }

        $password = self::generatePassword();
        $now      = Time::now()->toDateTimeString();

        $model->builder()->insert([
            'name'          => $name,
            'username'      => $username,
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);

        return ['id' => (int) $model->db->insertID(), 'username' => $username, 'password' => $password];
    }

    /**
     * Ganti kata sandi admin dengan kata sandi acak baru.
     *
     * @throws InvalidArgumentException
     */
    public function resetPassword(string $username): string
    {
        $username = strtolower(trim($username));
        $model    = model(AdminModel::class);
        $admin    = $model->where('username', $username)->first();

        if ($admin === null) {
            throw new InvalidArgumentException('Admin "' . $username . '" tidak ditemukan.');
        }

        $password = self::generatePassword();

        $model->builder()->where('id', (int) $admin['id'])->update([
            'password_hash' => password_hash($password, PASSWORD_DEFAULT),
            'updated_at'    => Time::now()->toDateTimeString(),
        ]);

        return $password;
    }
}
