<?php

namespace App\Controllers\Admin;

use App\Services\VoterType;

/**
 * Data siswa di panel admin. Route: admin/students (filter adminauth).
 */
class StudentController extends VoterController
{
    protected function voterType(): VoterType
    {
        return VoterType::Student;
    }
}
