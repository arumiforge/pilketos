<?php

namespace App\Controllers;

use App\Libraries\CandidateTheme;
use App\Models\CandidateModel;
use App\Models\ElectionModel;

class Home extends BaseController
{
    public function index()
    {
        return view('home/index', [
            'election'   => model(ElectionModel::class)->getCurrentElection(),
            // Teaser publik: hanya nomor, nama, dan warna tema (tanpa data suara).
            'candidates' => CandidateTheme::presentAll(model(CandidateModel::class)->getActiveCandidates()),
        ]);
    }
}
