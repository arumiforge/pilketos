<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;
use App\Models\ElectionModel;

class DashboardController extends BaseController
{
    public function index()
    {
        return view('admin/dashboard', [
            'title'    => 'Dasbor Admin',
            'admin'    => model(AdminModel::class)->findForSession((int) session()->get('admin_id')),
            'election' => model(ElectionModel::class)->getCurrentElection(),
        ]);
    }
}
