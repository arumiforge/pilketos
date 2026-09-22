<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $data = [
            'name' => session()->get('teacher_name'),
        ];

        return view('teacher/dashboard', $data);
    }
}
