<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Suara siswa. Satu baris = satu kali memilih.
 *
 * - status LOCKED   : suara aktif (maksimal 1 per siswa per election, dijamin
 *                     unique key uq_student_votes_active di database).
 * - status UNLOCKED : suara lama yang dibuka admin; tetap disimpan sebagai
 *                     riwayat audit dan TIDAK dihitung dalam hasil.
 *
 * Kolom active_lock adalah generated column (read-only), jangan diisi.
 */
class StudentVoteModel extends Model
{
    public const STATUS_LOCKED   = 'LOCKED';
    public const STATUS_UNLOCKED = 'UNLOCKED';

    protected $table         = 'student_votes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'student_id',
        'candidate_id',
        'status',
        'voted_at',
        'unlocked_at',
        'device_info',
        'browser_info',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Suara aktif (LOCKED) milik seorang siswa pada election tertentu.
     */
    public function findActiveVote(int $electionId, int $studentId): ?array
    {
        return $this->where('election_id', $electionId)
            ->where('student_id', $studentId)
            ->where('status', self::STATUS_LOCKED)
            ->first();
    }

    public function hasVoted(int $electionId, int $studentId): bool
    {
        return $this->findActiveVote($electionId, $studentId) !== null;
    }
}
