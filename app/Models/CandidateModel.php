<?php

namespace App\Models;

use CodeIgniter\Model;

class CandidateModel extends Model
{
    protected $table         = 'candidates';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nomor_urut',
        'nama_ketua',
        'nama_wakil',
        'foto_ketua',
        'foto_wakil',
        'visi',
        'misi',
        'theme_name',
        'theme_background',
        'theme_accent',
        'theme_asset',
        'status_aktif',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nomor_urut' => 'required|is_natural_no_zero|is_unique[candidates.nomor_urut,id,{id}]',
        'nama_ketua' => 'required|max_length[150]',
        'nama_wakil' => 'required|max_length[150]',
    ];

    /**
     * Ambil pasangan calon aktif, diurutkan berdasarkan nomor urut.
     */
    public function getActiveCandidates(): array
    {
        return $this->where('status_aktif', 1)
            ->orderBy('nomor_urut', 'ASC')
            ->findAll();
    }
}
