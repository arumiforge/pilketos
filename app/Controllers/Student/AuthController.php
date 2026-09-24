<?php

namespace App\Controllers\Student;

use App\Controllers\BaseController;
use App\Models\StudentModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'student') {
            return redirect()->to('siswa');
        }

        return view('student/login', ['title' => 'Masuk Siswa']);
    }

    public function attemptLogin()
    {
        // Normalisasi: NISN tanpa spasi; kode unik boleh diketik 01-03-2013 / 01/03/2013.
        $data = [
            'nisn'     => preg_replace('/\s+/', '', (string) $this->request->getPost('nisn')),
            'kodeunik' => preg_replace('/[\s\-\/.]+/', '', (string) $this->request->getPost('kodeunik')),
        ];

        $rules = [
            'nisn' => [
                'label'  => 'NISN',
                'rules'  => 'required|max_length[20]|regex_match[/^[0-9]+$/]',
                'errors' => [
                    'required'    => 'NISN wajib diisi.',
                    'max_length'  => 'NISN terlalu panjang.',
                    'regex_match' => 'NISN hanya berisi angka.',
                ],
            ],
            'kodeunik' => [
                'label'  => 'Kode unik',
                'rules'  => 'required|regex_match[/^[0-9]{8}$/]',
                'errors' => [
                    'required'    => 'Kode unik wajib diisi.',
                    'regex_match' => 'Kode unik terdiri dari 8 angka tanggal lahir, contoh 01032013.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return $this->failLogin('nisn', $data['nisn'], $this->validator->getErrors());
        }

        $wait = $this->loginBlockedSeconds('student', $data['nisn']);
        if ($wait > 0) {
            return $this->failLogin(
                'nisn',
                $data['nisn'],
                "Terlalu banyak percobaan masuk. Coba lagi dalam {$wait} detik.",
            );
        }

        $student = model(StudentModel::class)->findForLogin($data['nisn'], $data['kodeunik']);

        if (! $student) {
            $this->recordLoginFailure('student', $data['nisn']);

            return $this->failLogin(
                'nisn',
                $data['nisn'],
                'NISN atau kode unik belum cocok. Coba periksa lagi, ya.',
            );
        }

        $this->clearLoginFailures('student', $data['nisn']);
        $this->startAuthSession('student', (int) $student['id']);

        return redirect()->to('siswa');
    }

    public function logout()
    {
        $this->endAuthSession();

        return redirect()->to('/');
    }
}
