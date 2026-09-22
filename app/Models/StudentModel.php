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
        'status_aktif',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * NISN = angka (string, leading zero dipertahankan).
     * Kode unik = tanggal lahir DDMMYYYY, tepat 8 digit.
     */
    protected $validationRules = [
        'nisn'          => 'required|max_length[20]|regex_match[/^[0-9]+$/]|is_unique[students.nisn,id,{id}]',
        'name'          => 'required|max_length[150]',
        'jenis_kelamin' => 'required|in_list[L,P]',
        'kelas'         => 'required|max_length[20]',
        'nomor_absen'   => 'permit_empty|is_natural_no_zero',
        'kodeunik'      => 'required|regex_match[/^[0-9]{8}$/]',
        'status_aktif'  => 'permit_empty|in_list[0,1]',
    ];

    /**
     * Cari siswa aktif berdasarkan NISN + kode unik untuk keperluan login.
     * Keduanya diperlakukan sebagai string agar leading zero tidak hilang.
     */
    public function findForLogin(string $nisn, string $kodeunik): ?array
    {
        return $this->where('nisn', $nisn)
            ->where('kodeunik', $kodeunik)
            ->where('status_aktif', 1)
            ->first();
    }

    /**
     * Ambil siswa aktif berdasarkan id sesi. Null bila dihapus/dinonaktifkan.
     */
    public function findActive(int $id): ?array
    {
        return $this->where('id', $id)
            ->where('status_aktif', 1)
            ->first();
    }
}
