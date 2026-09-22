<?php

namespace App\Controllers\Student;

use App\Controllers\VotingController;
use App\Services\VoterType;

/**
 * Voting siswa: suara ditulis ke student_votes dengan student_id dari sesi.
 * Route berada di grup filter studentauth (app/Config/Routes.php).
 */
class VoteController extends VotingController
{
    protected function voterType(): VoterType
    {
        return VoterType::Student;
    }
}
