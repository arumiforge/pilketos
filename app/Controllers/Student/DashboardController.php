<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Libraries\CandidateTheme;
use App\Models\StudentModel;
use App\Services\VoterType;

class DashboardController extends BaseController
{
    public function index()
    {
        // Data selalu diambil dari database (bukan dari sesi) agar selalu terbaru.
        // StudentAuthFilter sudah menjamin siswa ada dan aktif.
        $studentId = (int) session()->get('student_id');
        $state     = service('voting')->ballotState(VoterType::Student, $studentId);

        return view('student/dashboard', [
            'title'     => 'Dasbor Siswa',
            'type'      => VoterType::Student,
            'student'   => model(StudentModel::class)->findActive($studentId),
            'election'  => $state['election'],
            'vote'      => $state['vote'],
            'candidate' => $state['candidate'] === null ? null : CandidateTheme::present($state['candidate']),
            'canVote'   => $state['canVote'],
        ]);
    }
}
