<?php

namespace App\Models;

use CodeIgniter\Model;

class VoteUnlockLogModel extends Model
{
    protected $table         = 'vote_unlock_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'student_id',
        'teacher_id',
        'admin_id',
        'previous_candidate_id',
        'previous_voted_at',
        'reason',
        'unlocked_at',
    ];

    // Tabel ini hanya punya kolom unlocked_at, bukan created_at/updated_at.
    protected $useTimestamps = false;
}
