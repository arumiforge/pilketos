<?php

namespace App\Controllers\Admin;

use App\Libraries\AdminAccount;
use App\Models\AdminModel;
use App\Models\AuditLogModel;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\I18n\Time;

/**
 * Akun Admin (Stage 13): ganti nama pengguna dan kata sandi admin yang
 * sedang masuk, langsung dari panel admin.
 *
 * - Setiap perubahan meminta kata sandi saat ini. Salah kata sandi dibatasi
 *   seperti login (kuota per admin + per IP, BaseController throttle).
 * - Kata sandi tidak pernah disalin ke sesi (tanpa withInput()); yang diisi
 *   ulang hanya nama pengguna baru.
 * - Ganti kata sandi memperbarui cap sesi (AdminModel::SESSION_STAMP_KEY):
 *   sesi ini tetap masuk, sesi admin yang sama di perangkat lain keluar.
 * - Tercatat di audit log (tanpa kata sandi).
 */
class AccountController extends AdminController
{
    private const THROTTLE_SCOPE = 'admin_account';

    /**
     * GET admin/akun
     */
    public function index()
    {
        return $this->render('admin/account/index', [
            'title'  => 'Akun Admin',
            'form'   => (string) (session()->getFlashdata('account_form') ?? ''),
            'errors' => (array) (session()->getFlashdata('errors') ?? []),
        ], 'account');
    }

    /**
     * POST admin/akun/nama-pengguna
     */
    public function username(): RedirectResponse
    {
        $admin    = $this->admin();
        $username = strtolower(trim((string) $this->request->getPost('username')));
        $current  = (string) $this->request->getPost('current_password');
        $errors   = [];

        if ($username === '') {
            $errors['username'] = 'Nama pengguna baru wajib diisi.';
        } elseif (preg_match(AdminAccount::USERNAME_PATTERN, $username) !== 1) {
            $errors['username'] = 'Nama pengguna 3-50 karakter: huruf kecil, angka, titik, strip, atau garis bawah, diawali huruf/angka.';
        } elseif ($username === ($admin['username'] ?? '')) {
            $errors['username'] = 'Nama pengguna baru sama dengan yang sekarang.';
        } elseif (model(AdminModel::class)->where('username', $username)->where('id !=', $this->adminId())->countAllResults() > 0) {
            $errors['username'] = 'Nama pengguna "' . $username . '" sudah dipakai admin lain.';
        }

        $passwordError = $this->checkCurrentPassword($current);
        if ($passwordError !== null) {
            $errors['current_password'] = $passwordError;
        }

        if ($errors !== []) {
            return $this->fail('username', $errors)->with('old_username', $username);
        }

        model(AdminModel::class)->builder()->where('id', $this->adminId())->update([
            'username'   => $username,
            'updated_at' => Time::now()->toDateTimeString(),
        ]);

        model(AuditLogModel::class)->log(
            $this->adminId(),
            AuditLogModel::ADMIN_USERNAME,
            'Nama pengguna admin diganti dari @' . ($admin['username'] ?? '') . ' menjadi @' . $username . '.',
        );

        return redirect()->to('admin/akun')->with('success', 'Nama pengguna diganti menjadi ' . $username . '. Gunakan nama ini saat masuk berikutnya.');
    }

    /**
     * POST admin/akun/kata-sandi
     */
    public function password(): RedirectResponse
    {
        $current = (string) $this->request->getPost('current_password');
        $new     = (string) $this->request->getPost('new_password');
        $confirm = (string) $this->request->getPost('new_password_confirm');
        $errors  = [];

        if ($new === '') {
            $errors['new_password'] = 'Kata sandi baru wajib diisi.';
        } elseif (mb_strlen($new) < AdminModel::PASSWORD_MIN_LENGTH) {
            $errors['new_password'] = 'Kata sandi baru minimal ' . AdminModel::PASSWORD_MIN_LENGTH . ' karakter.';
        } elseif (strlen($new) > AdminModel::PASSWORD_MAX_BYTES) {
            $errors['new_password'] = 'Kata sandi baru terlalu panjang (maksimal ' . AdminModel::PASSWORD_MAX_BYTES . ' karakter).';
        } elseif (trim($new) === '') {
            $errors['new_password'] = 'Kata sandi baru tidak boleh hanya berisi spasi.';
        } elseif (strtolower($new) === strtolower((string) ($this->admin()['username'] ?? ''))) {
            $errors['new_password'] = 'Kata sandi baru tidak boleh sama dengan nama pengguna.';
        }

        if (! isset($errors['new_password']) && ! hash_equals($new, $confirm)) {
            $errors['new_password_confirm'] = 'Ulangi kata sandi baru dengan isi yang sama.';
        }

        $passwordError = $this->checkCurrentPassword($current);
        if ($passwordError !== null) {
            $errors['current_password'] = $passwordError;
        } elseif (! isset($errors['new_password']) && hash_equals($current, $new)) {
            $errors['new_password'] = 'Kata sandi baru harus berbeda dari kata sandi saat ini.';
        }

        if ($errors !== []) {
            return $this->fail('password', $errors);
        }

        $model = model(AdminModel::class);
        $hash  = password_hash($new, PASSWORD_DEFAULT);

        $model->builder()->where('id', $this->adminId())->update([
            'password_hash' => $hash,
            'updated_at'    => Time::now()->toDateTimeString(),
        ]);

        // Sesi ini tetap masuk dengan id sesi baru; sesi lain admin ini keluar.
        $session = session();
        $session->regenerate(true);
        $session->set(AdminModel::SESSION_STAMP_KEY, AdminModel::stampFor($hash));

        model(AuditLogModel::class)->log($this->adminId(), AuditLogModel::ADMIN_PASSWORD, 'Kata sandi admin diganti.');

        return redirect()->to('admin/akun')->with('success', 'Kata sandi diganti. Perangkat lain yang masih masuk dengan akun ini otomatis keluar.');
    }

    /**
     * Pesan galat untuk kata sandi saat ini, atau null bila cocok.
     */
    private function checkCurrentPassword(string $password): ?string
    {
        $identifier = (string) $this->adminId();

        $wait = $this->loginBlockedSeconds(self::THROTTLE_SCOPE, $identifier);
        if ($wait > 0) {
            return "Terlalu banyak percobaan. Silakan coba lagi dalam {$wait} detik.";
        }

        if ($password === '') {
            return 'Kata sandi saat ini wajib diisi.';
        }

        if (strlen($password) > 255 || ! model(AdminModel::class)->passwordMatches($this->adminId(), $password)) {
            $this->recordLoginFailure(self::THROTTLE_SCOPE, $identifier);

            return 'Kata sandi saat ini tidak sesuai.';
        }

        $this->clearLoginFailures(self::THROTTLE_SCOPE, $identifier);

        return null;
    }

    /**
     * @param array<string, string> $errors
     */
    private function fail(string $form, array $errors): RedirectResponse
    {
        return redirect()->to('admin/akun')
            ->with('account_form', $form)
            ->with('errors', $errors);
    }
}
