<?php

namespace App\Controllers;

use App\Models\ElectionModel;

class Home extends BaseController
{
    public function index()
    {
        return view('home/index', [
            'election' => model(ElectionModel::class)->getCurrentElection(),
        ]);
    }
}
