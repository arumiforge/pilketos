<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    /**
     * Stage 13: key sesi berisi cap kata sandi admin (sessionStamp()).
     * Dipasang saat login dan saat admin mengganti kata sandi; AdminAuthFilter
     * menolak sesi yang capnya tidak cocok lagi, jadi mengganti kata sandi
     * mengeluarkan sesi admin itu di perangkat lain.
     */
    public const SESSION_STAMP_KEY = 'admin_stamp';

    /**
     * Stage 13: batas kata sandi admin (password_hash() bcrypt hanya memakai
     * 72 byte pertama, jadi kata sandi lebih panjang ditolak).
     */
    public const PASSWORD_MIN_LENGTH = 8;
    public const PASSWORD_MAX_BYTES  = 72;

    protected $table          = 'admins';
    protected $primaryKey     = 'id';
    protected $returnType     = 'array';
    protected $useSoftDeletes = false;
    protected $allowedFields  = ['name', 'username', 'password_hash'];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'name'     => 'required|max_length[150]',
        'username' => 'required|max_length[100]|is_unique[admins.username,id,{id}]',
    ];

    /**
     * Cari admin berdasarkan username dan verifikasi password.
     * Mengembalikan baris admin (tanpa password_hash) bila valid, null bila tidak.
     */
    public function verifyCredentials(string $username, string $password): ?array
    {
        $admin = $this->where('username', $username)->first();

        if (! $admin) {
            // Samakan biaya waktu dengan password_verify agar keberadaan
            // username tidak dapat ditebak dari lama respons.
            password_hash($password, PASSWORD_DEFAULT);

            return null;
        }

        if (! password_verify($password, $admin['password_hash'])) {
            return null;
        }

        unset($admin['password_hash']);

        return $admin;
    }

    /**
     * Ambil admin untuk sesi aktif (tanpa password_hash).
     */
    public function findForSession(int $id): ?array
    {
        return $this->select('id, name, username')->find($id);
    }

    /**
     * Cap sesi admin (Stage 13): turunan satu arah dari password_hash, berubah
     * setiap kata sandi diganti. Null bila admin tidak ada.
     */
    public function sessionStamp(int $id): ?string
    {
        $row = $this->builder()->select('password_hash')->where('id', $id)->get()->getRowArray();

        return $row === null ? null : self::stampFor((string) $row['password_hash']);
    }

    public static function stampFor(string $passwordHash): string
    {
        return substr(hash('sha256', 'admin-session|' . $passwordHash), 0, 32);
    }

    /**
     * Cocokkan kata sandi admin berdasarkan id (halaman Akun Admin).
     */
    public function passwordMatches(int $id, string $password): bool
    {
        $row = $this->builder()->select('password_hash')->where('id', $id)->get()->getRowArray();

        return $row !== null && password_verify($password, (string) $row['password_hash']);
    }
}
