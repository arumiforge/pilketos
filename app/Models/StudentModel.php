<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentModel extends Model
{
    protected $table         = 'students';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nisn',
        'name',
        'jenis_kelamin',
        'kelas',
        'nomor_absen',
        'kodeunik',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nisn'          => 'required|max_length[20]|is_unique[students.nisn,id,{id}]',
        'name'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'kelas'         => 'required|max_length[20]',
        'nomor_absen'   => 'permit_empty|is_natural',
        'kodeunik'      => 'required|max_length[20]',
    ];

    /**
     * Cari siswa berdasarkan NISN + kode unik untuk keperluan login.
     * Keduanya diperlakukan sebagai string agar leading zero tidak hilang.
     */
    public function findForLogin(string $nisn, string $kodeunik): ?array
    {
        return $this->where('nisn', $nisn)
            ->where('kodeunik', $kodeunik)
            ->first();
    }
}
