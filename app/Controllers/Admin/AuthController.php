<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'admin') {
            return redirect()->to('admin');
        }

        return view('admin/login', ['title' => 'Panel Admin']);
    }

    public function attemptLogin()
    {
        $data = [
            'username' => trim((string) $this->request->getPost('username')),
            'password' => (string) $this->request->getPost('password'),
        ];

        $rules = [
            'username' => [
                'label'  => 'Nama pengguna',
                'rules'  => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama pengguna wajib diisi.',
                    'max_length' => 'Nama pengguna terlalu panjang.',
                ],
            ],
            'password' => [
                'label'  => 'Kata sandi',
                'rules'  => 'required|max_length[255]',
                'errors' => [
                    'required'   => 'Kata sandi wajib diisi.',
                    'max_length' => 'Kata sandi terlalu panjang.',
                ],
            ],
        ];

        if (! $this->validateData($data, $rules)) {
            return $this->failLogin('username', $data['username'], $this->validator->getErrors());
        }

        $wait = $this->loginBlockedSeconds('admin', $data['username']);
        if ($wait > 0) {
            return $this->failLogin(
                'username',
                $data['username'],
                "Terlalu banyak percobaan masuk. Silakan coba lagi dalam {$wait} detik.",
            );
        }

        $admin = model(AdminModel::class)->verifyCredentials($data['username'], $data['password']);

        if (! $admin) {
            $this->recordLoginFailure('admin', $data['username']);

            return $this->failLogin(
                'username',
                $data['username'],
                'Nama pengguna atau kata sandi tidak sesuai.',
            );
        }

        $this->clearLoginFailures('admin', $data['username']);
        $this->startAuthSession('admin', (int) $admin['id']);
        session()->set(AdminModel::SESSION_STAMP_KEY, model(AdminModel::class)->sessionStamp((int) $admin['id']));

        return redirect()->to('admin');
    }

    public function logout()
    {
        $this->endAuthSession();

        return redirect()->to('admin/masuk');
    }
}
