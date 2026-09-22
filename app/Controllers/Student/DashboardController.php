<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $data = [
            'name'        => session()->get('student_name'),
            'kelas'       => session()->get('student_kelas'),
            'nomor_absen' => session()->get('student_nomor_absen'),
        ];

        return view('student/dashboard', $data);
    }
}
