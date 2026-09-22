<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Suara guru. Satu baris = satu kali memilih.
 *
 * - status LOCKED   : suara aktif (maksimal 1 per guru per election, dijamin
 *                     unique key uq_teacher_votes_active di database).
 * - status UNLOCKED : suara lama yang dibuka admin; tetap disimpan sebagai
 *                     riwayat audit dan TIDAK dihitung dalam hasil.
 *
 * Kolom active_lock adalah generated column (read-only), jangan diisi.
 */
class TeacherVoteModel extends Model
{
    public const STATUS_LOCKED   = 'LOCKED';
    public const STATUS_UNLOCKED = 'UNLOCKED';

    protected $table         = 'teacher_votes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'teacher_id',
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
     * Suara aktif (LOCKED) milik seorang guru pada election tertentu.
     */
    public function findActiveVote(int $electionId, int $teacherId): ?array
    {
        return $this->where('election_id', $electionId)
            ->where('teacher_id', $teacherId)
            ->where('status', self::STATUS_LOCKED)
            ->first();
    }

    public function hasVoted(int $electionId, int $teacherId): bool
    {
        return $this->findActiveVote($electionId, $teacherId) !== null;
    }
}
