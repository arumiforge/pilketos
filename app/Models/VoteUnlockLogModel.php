<?php

namespace App\Models;

use CodeIgniter\Model;

/**
 * Log unlock hak suara. Satu baris per tindakan unlock admin.
 * Tepat satu pasangan (student_id + student_vote_id) ATAU
 * (teacher_id + teacher_vote_id) yang terisi (CHECK constraint).
 */
class VoteUnlockLogModel extends Model
{
    protected $table         = 'vote_unlock_logs';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'election_id',
        'student_id',
        'teacher_id',
        'student_vote_id',
        'teacher_vote_id',
        'admin_id',
        'reason',
        'unlocked_at',
    ];

    // Tabel ini hanya punya kolom unlocked_at, bukan created_at/updated_at.
    protected $useTimestamps = false;
}
