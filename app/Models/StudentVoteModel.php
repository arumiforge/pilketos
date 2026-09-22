<?php

namespace App\Models;

use CodeIgniter\Model;

class StudentVoteModel extends Model
{
    protected $table         = 'student_votes';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'student_id',
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
     * Cari vote aktif milik seorang siswa pada election tertentu.
     * Baris hanya ada selama hak suara terkunci (LOCKED).
     */
    public function findActiveVote(int $electionId, int $studentId): ?array
    {
        return $this->where('election_id', $electionId)
            ->where('student_id', $studentId)
            ->first();
    }

    public function hasVoted(int $electionId, int $studentId): bool
    {
        return $this->findActiveVote($electionId, $studentId) !== null;
    }
}
