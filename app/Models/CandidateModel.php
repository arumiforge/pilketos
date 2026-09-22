<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Pasangan calon Ketua & Wakil Ketua OSIS.
 *
 * Kolom file (foto_ketua, foto_wakil, theme_background) dan isi theme_asset
 * menyimpan NAMA FILE di public/uploads/candidates/ (bukan URL penuh).
 * theme_asset = JSON object, contoh: {"hero":"x.webp","texture":"y.webp","artwork":"z.webp","poster":"p.webp"}.
 */
class CandidateModel extends Model
{
    public const UPLOAD_DIR = 'uploads/candidates';

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

    protected array $casts = [
        'theme_asset' => '?json-array',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nomor_urut'   => 'required|is_natural_no_zero|is_unique[candidates.nomor_urut,id,{id}]',
        'nama_ketua'   => 'required|max_length[150]',
        'nama_wakil'   => 'required|max_length[150]',
        'theme_accent' => 'permit_empty|regex_match[/^#[0-9A-Fa-f]{6}$/]',
        'status_aktif' => 'permit_empty|in_list[0,1]',
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

    /**
     * URL publik untuk asset kandidat, atau null bila belum diunggah.
     */
    public static function assetUrl(?string $filename): ?string
    {
        if ($filename === null || $filename === '') {
            return null;
        }

        return base_url(self::UPLOAD_DIR . '/' . rawurlencode(basename($filename)));
    }
}
