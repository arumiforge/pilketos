<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\TeacherModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'teacher') {
            return redirect()->to('/teacher/dashboard');
        }

        return view('teacher/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'nip' => [
                'label' => 'NIP',
                'rules' => 'required|max_length[30]',
                'errors' => [
                    'required'   => 'NIP wajib diisi.',
                    'max_length' => 'NIP terlalu panjang.',
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
        if (! $this->loginAttemptAllowed('login-teacher-' . $ip, 8, 60)) {
            return redirect()->back()->withInput()
                ->with('error', 'Terlalu banyak percobaan masuk. Silakan coba lagi sebentar lagi.');
        }

        $nip      = trim((string) $this->request->getPost('nip'));
        $kodeunik = trim((string) $this->request->getPost('kodeunik'));

        $teacherModel = new TeacherModel();
        $teacher      = $teacherModel->findForLogin($nip, $kodeunik);

        if (! $teacher) {
            return redirect()->back()->withInput()
                ->with('error', 'NIP atau kode unik tidak sesuai. Silakan periksa kembali.');
        }

        session()->regenerate();
        session()->set([
            'user_type'    => 'teacher',
            'teacher_id'   => $teacher['id'],
            'teacher_name' => $teacher['name'],
            'isLoggedIn'   => true,
        ]);

        return redirect()->to('/teacher/dashboard');
    }

    public function logout()
    {
        session()->remove([
            'user_type',
            'teacher_id',
            'teacher_name',
            'isLoggedIn',
        ]);
        session()->destroy();

        return redirect()->to('/');
    }
}
