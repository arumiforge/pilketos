<?php

namespace App\Models;

use CodeIgniter\Model;

class ElectionModel extends Model
{
    protected $table         = 'elections';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'nama',
        'tahun',
        'start_at',
        'end_at',
        'status',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'nama'     => 'required|max_length[150]',
        'tahun'    => 'required|is_natural_no_zero',
        'start_at' => 'required|valid_date',
        'end_at'   => 'required|valid_date',
    ];

    /**
     * Ambil election yang sedang berlaku (yang terbaru berdasarkan tahun/id).
     * Stage 1 hanya menyediakan satu election aktif dari seeder.
     */
    public function getCurrentElection(): ?array
    {
        return $this->orderBy('id', 'DESC')->first();
    }

    /**
     * Hitung status election secara live berdasarkan waktu server,
     * bukan hanya mengandalkan kolom status yang tersimpan.
     * Server time selalu menjadi sumber kebenaran (lihat MASTER section 16).
     */
    public function resolveStatus(array $election): string
    {
        $now   = new \DateTime('now');
        $start = new \DateTime($election['start_at']);
        $end   = new \DateTime($election['end_at']);

        if ($now < $start) {
            return 'UPCOMING';
        }

        if ($now > $end) {
            return 'FINISHED';
        }

        return 'ONGOING';
    }
}
