<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\ElectionModel;
use App\Models\StudentModel;

class DashboardController extends BaseController
{
    public function index()
    {
        // Data selalu diambil dari database (bukan dari sesi) agar selalu terbaru.
        // StudentAuthFilter sudah menjamin siswa ada dan aktif.
        $student = model(StudentModel::class)->findActive((int) session()->get('student_id'));

        return view('student/dashboard', [
            'title'    => 'Dasbor Siswa',
            'student'  => $student,
            'election' => model(ElectionModel::class)->getCurrentElection(),
        ]);
    }
}
