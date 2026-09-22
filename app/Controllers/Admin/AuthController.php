<?php

namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use App\Models\AdminModel;

class AuthController extends BaseController
{
    public function loginForm()
    {
        if (session()->get('user_type') === 'admin') {
            return redirect()->to('/admin/dashboard');
        }

        return view('admin/login');
    }

    public function attemptLogin()
    {
        $rules = [
            'username' => [
                'label' => 'Nama pengguna',
                'rules' => 'required|max_length[100]',
                'errors' => [
                    'required'   => 'Nama pengguna wajib diisi.',
                    'max_length' => 'Nama pengguna terlalu panjang.',
                ],
            ],
            'password' => [
                'label' => 'Kata sandi',
                'rules' => 'required',
                'errors' => [
                    'required' => 'Kata sandi wajib diisi.',
                ],
            ],
        ];

        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $ip = $this->request->getIPAddress();
        if (! $this->loginAttemptAllowed('login-admin-' . $ip, 5, 60)) {
            return redirect()->back()->withInput()
                ->with('error', 'Terlalu banyak percobaan masuk. Silakan coba lagi sebentar lagi.');
        }

        $username = trim((string) $this->request->getPost('username'));
        $password = (string) $this->request->getPost('password');

        $adminModel = new AdminModel();
        $admin      = $adminModel->verifyCredentials($username, $password);

        if (! $admin) {
            return redirect()->back()->withInput()
                ->with('error', 'Nama pengguna atau kata sandi tidak sesuai.');
        }

        session()->regenerate();
        session()->set([
            'user_type'  => 'admin',
            'admin_id'   => $admin['id'],
            'admin_name' => $admin['name'],
            'isLoggedIn' => true,
        ]);

        return redirect()->to('/admin/dashboard');
    }

    public function logout()
    {
        session()->remove([
            'user_type',
            'admin_id',
            'admin_name',
            'isLoggedIn',
        ]);
        session()->destroy();

        return redirect()->to('/admin/login');
    }
}
