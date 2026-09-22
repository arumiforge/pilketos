<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherModel extends Model
{
    protected $table         = 'teachers';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nip',
        'name',
        'kodeunik',
        'status_aktif',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * NIP disimpan sebagai string (leading zero dipertahankan).
     * Kode unik = tanggal lahir DDMMYYYY, tepat 8 digit.
     */
    protected $validationRules = [
        'nip'          => 'required|max_length[30]|is_unique[teachers.nip,id,{id}]',
        'name'         => 'required|max_length[150]',
        'kodeunik'     => 'required|regex_match[/^[0-9]{8}$/]',
        'status_aktif' => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Cari guru aktif berdasarkan NIP + kode unik untuk keperluan login.
     * Guru non-aktif (status_aktif = 0) tidak dapat login.
     */
    public function findForLogin(string $nip, string $kodeunik): ?array
    {
        return $this->where('nip', $nip)
            ->where('kodeunik', $kodeunik)
            ->where('status_aktif', 1)
            ->first();
    }

    /**
     * Ambil guru aktif berdasarkan id sesi. Null bila dihapus/dinonaktifkan.
     */
    public function findActive(int $id): ?array
    {
        return $this->where('id', $id)
            ->where('status_aktif', 1)
            ->first();
    }
}
