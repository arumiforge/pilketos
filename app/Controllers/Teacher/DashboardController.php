<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\ElectionModel;
use App\Models\TeacherModel;

class DashboardController extends BaseController
{
    public function index()
    {
        // Data selalu diambil dari database (bukan dari sesi) agar selalu terbaru.
        // TeacherAuthFilter sudah menjamin guru ada dan aktif.
        $teacher = model(TeacherModel::class)->findActive((int) session()->get('teacher_id'));

        return view('teacher/dashboard', [
            'title'    => 'Dasbor Guru',
            'teacher'  => $teacher,
            'election' => model(ElectionModel::class)->getCurrentElection(),
        ]);
    }
}
