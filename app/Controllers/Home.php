<?php

namespace App\Controllers;

use App\Models\ElectionModel;

class Home extends BaseController
{
    public function index()
    {
        $electionModel = new ElectionModel();
        $election      = $electionModel->getCurrentElection();

        $status = $election ? $electionModel->resolveStatus($election) : null;

        return view('home/index', [
            'election' => $election,
            'status'   => $status,
        ]);
    }
}
