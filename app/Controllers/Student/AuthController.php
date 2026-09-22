<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\StudentModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'student') {
            return redirect()->to('/student/dashboard');
        }

        return view('student/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'nisn' => [
                'label' => 'NISN',
                'rules' => 'required|max_length[20]',
                'errors' => [
                    'required'   => 'NISN wajib diisi.',
                    'max_length' => 'NISN terlalu panjang.',
                ],
            ],
            'kodeunik' => [
                'label' => 'Kode unik',
                'rules' => 'required|max_length[20]',
                'errors' => [
                    'required'   => 'Kode unik wajib diisi.',
                    'max_length' => 'Kode unik terlalu panjang.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $ip = $this->request->getIPAddress();
        if (! $this->loginAttemptAllowed('login-student-' . $ip, 8, 60)) {
            return redirect()->back()->withInput()
                ->with('error', 'Terlalu banyak percobaan masuk. Coba lagi sebentar lagi, ya.');
        }

        $nisn     = trim((string) $this->request->getPost('nisn'));
        $kodeunik = trim((string) $this->request->getPost('kodeunik'));

        $studentModel = new StudentModel();
        $student      = $studentModel->findForLogin($nisn, $kodeunik);

        if (! $student) {
            return redirect()->back()->withInput()
                ->with('error', 'NISN atau kode unik belum cocok. Coba periksa lagi, ya.');
        }

        session()->regenerate();
        session()->set([
            'user_type'           => 'student',
            'student_id'          => $student['id'],
            'student_name'        => $student['name'],
            'student_kelas'       => $student['kelas'],
            'student_nomor_absen' => $student['nomor_absen'],
            'isLoggedIn'          => true,
        ]);

        return redirect()->to('/student/dashboard');
    }

    public function logout()
    {
        session()->remove([
            'user_type',
            'student_id',
            'student_name',
            'student_kelas',
            'student_nomor_absen',
            'isLoggedIn',
        ]);
        session()->destroy();

        return redirect()->to('/');
    }
}
