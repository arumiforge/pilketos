<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;

class DashboardController extends BaseController
{
    public function index()
    {
        $data = [
            'name' => session()->get('admin_name'),
        ];

        return view('admin/dashboard', $data);
    }
}
