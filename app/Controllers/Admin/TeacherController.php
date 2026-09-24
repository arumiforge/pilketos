<?php

namespace App\Controllers\Admin;

use App\Services\VoterType;

/**
 * Data guru di panel admin. Route: admin/guru (filter adminauth).
 * Guru hanya pemilih, bukan admin.
 */
class TeacherController extends VoterController
{
    protected function voterType(): VoterType
    {
        return VoterType::Teacher;
    }
}
