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

    protected $validationRules = [
        'nip'      => 'required|max_length[30]|is_unique[teachers.nip,id,{id}]',
        'name'     => 'required|max_length[150]',
        'kodeunik' => 'required|max_length[20]',
    ];

    /**
     * Cari guru berdasarkan NIP + kode unik untuk keperluan login.
     * Guru non-aktif (status_aktif = 0) tidak dapat login.
     */
    public function findForLogin(string $nip, string $kodeunik): ?array
    {
        return $this->where('nip', $nip)
            ->where('kodeunik', $kodeunik)
            ->where('status_aktif', 1)
            ->first();
    }
}
