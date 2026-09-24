<?php

namespace App\Controllers\Teacher;

use App\Controllers\BaseController;
use App\Models\TeacherModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'teacher') {
            return redirect()->to('guru');
        }

        return view('teacher/login', ['title' => 'Masuk Guru']);
    }

    public function attemptLogin()
    {
        // Normalisasi: NIP tanpa spasi; kode unik boleh diketik 01-03-2006 / 01/03/2006.
        $data = [
            'nip'      => preg_replace('/\s+/', '', (string) $this->request->getPost('nip')),
            'kodeunik' => preg_replace('/[\s\-\/.]+/', '', (string) $this->request->getPost('kodeunik')),
        ];

        $rules = [
            'nip' => [
                'label'  => 'NIP',
                'rules'  => 'required|max_length[30]',
                'errors' => [
                    'required'   => 'NIP wajib diisi.',
                    'max_length' => 'NIP terlalu panjang.',
                ],
            ],
            'kodeunik' => [
                'label'  => 'Kode unik',
                'rules'  => 'required|regex_match[/^[0-9]{8}$/]',
                'errors' => [
                    'required'    => 'Kode unik wajib diisi.',
                    'regex_match' => 'Kode unik terdiri dari 8 angka tanggal lahir, contoh 01032006.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return $this->failLogin('nip', $data['nip'], $this->validator->getErrors());
        }

        $wait = $this->loginBlockedSeconds('teacher', $data['nip']);
        if ($wait > 0) {
            return $this->failLogin(
                'nip',
                $data['nip'],
                "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$wait} detik.",
            );
        }

        $teacher = model(TeacherModel::class)->findForLogin($data['nip'], $data['kodeunik']);

        if (! $teacher) {
            $this->recordLoginFailure('teacher', $data['nip']);

            return $this->failLogin(
                'nip',
                $data['nip'],
                'NIP atau kode unik tidak sesuai. Silakan periksa kembali.',
            );
        }

        $this->clearLoginFailures('teacher', $data['nip']);
        $this->startAuthSession('teacher', (int) $teacher['id']);

        return redirect()->to('guru');
    }

    public function logout()
    {
        $this->endAuthSession();

        return redirect()->to('/');
    }
}
