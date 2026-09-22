<?php

namespace App\Controllers\Teacher;

use App\Controllers\VotingController;
use App\Services\VoterType;

/**
 * Voting guru: suara ditulis ke teacher_votes dengan teacher_id dari sesi.
 * Route berada di grup filter teacherauth (app/Config/Routes.php).
 */
class VoteController extends VotingController
{
    protected function voterType(): VoterType
    {
        return VoterType::Teacher;
    }
}
