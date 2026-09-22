<?php

namespace App\Models;

use CodeIgniter\Model;

class AdminModel extends Model
{
    protected $table            = 'admins';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $allowedFields    = ['name', 'username', 'password_hash'];

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
            return null;
        }

        if (! password_verify($password, $admin['password_hash'])) {
            return null;
        }

        unset($admin['password_hash']);

        return $admin;
    }
}
