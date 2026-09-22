<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Libraries\CandidateTheme;
use App\Models\TeacherModel;
use App\Services\VoterType;

class DashboardController extends BaseController
{
    public function index()
    {
        // Data selalu diambil dari database (bukan dari sesi) agar selalu terbaru.
        // TeacherAuthFilter sudah menjamin guru ada dan aktif.
        $teacherId = (int) session()->get('teacher_id');
        $state     = service('voting')->ballotState(VoterType::Teacher, $teacherId);

        return view('teacher/dashboard', [
            'title'     => 'Dasbor Guru',
            'type'      => VoterType::Teacher,
            'teacher'   => model(TeacherModel::class)->findActive($teacherId),
            'election'  => $state['election'],
            'vote'      => $state['vote'],
            'candidate' => $state['candidate'] === null ? null : CandidateTheme::present($state['candidate']),
            'canVote'   => $state['canVote'],
        ]);
    }
}
