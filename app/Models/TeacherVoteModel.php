<?php

namespace App\Models;

use CodeIgniter\Model;

class TeacherVoteModel extends Model
{
    protected $table         = 'teacher_votes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'teacher_id',
        'candidate_id',
        'status',
        'voted_at',
        'device_info',
        'browser_info',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    /**
     * Cari vote aktif milik seorang guru pada election tertentu.
     * Baris hanya ada selama hak suara terkunci (LOCKED).
     */
    public function findActiveVote(int $electionId, int $teacherId): ?array
    {
        return $this->where('election_id', $electionId)
            ->where('teacher_id', $teacherId)
            ->first();
    }

    public function hasVoted(int $electionId, int $teacherId): bool
    {
        return $this->findActiveVote($electionId, $teacherId) !== null;
    }
}
