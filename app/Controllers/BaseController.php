<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use Psr\Log\LoggerInterface;

/**
 * Base Controller
 *
 * Semua controller aplikasi (public, student, teacher, admin) diturunkan
 * dari kelas ini. Berisi helper bersama: sesi autentikasi dan throttling login.
 */
abstract class BaseController extends Controller
{
    /**
     * Waktu (epoch detik, jam server) request terakhir yang lolos filter auth.
     * Stage 4: dipakai AuthFilter untuk mengakhiri sesi pemilih yang ditinggal.
     * Bukan data profil/rahasia.
     */
    public const AUTH_SEEN_KEY = 'auth_seen_at';

    /**
     * Seluruh key sesi autentikasi. Dibersihkan setiap login/logout agar
     * tidak ada sisa identitas role lain di sesi yang sama.
     */
    public const AUTH_SESSION_KEYS = ['user_type', 'admin_id', 'student_id', 'teacher_id', 'isLoggedIn', self::AUTH_SEEN_KEY, 'admin_stamp'];

    /**
     * Batas login GAGAL per akun (NISN/NIP/username). Kode unik berupa tanggal
     * lahir mudah ditebak, jadi batas per akun adalah proteksi utama.
     * Token bucket: 5 kegagalan, lalu pulih 1 percobaan per 60 detik.
     */
    private const THROTTLE_ACCOUNT_CAPACITY = 5;
    private const THROTTLE_ACCOUNT_SECONDS  = 300;

    /**
     * Batas login GAGAL per IP. Sengaja longgar karena seluruh siswa satu
     * sekolah bisa keluar melalui satu IP (Wi-Fi/NAT/lab komputer).
     */
    private const THROTTLE_IP_CAPACITY = 30;
    private const THROTTLE_IP_SECONDS  = 60;

    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    /**
     * Helper url, form, dan app dimuat global lewat app/Config/Autoload.php.
     */
    protected $helpers = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Mulai sesi login baru untuk satu role. Sesi lama di-regenerate
     * (anti session fixation) dan identitas role lain dihapus.
     * Sesi hanya menyimpan tipe user, id, status login, dan waktu aktivitas
     * terakhir (Stage 4, batas idle pemilih).
     *
     * @param 'admin'|'student'|'teacher' $userType
     */
    protected function startAuthSession(string $userType, int $userId): void
    {
        $session = session();
        $session->remove(self::AUTH_SESSION_KEYS);
        $session->regenerate(true);
        $session->set([
            'user_type'         => $userType,
            $userType . '_id'   => $userId,
            'isLoggedIn'        => true,
            self::AUTH_SEEN_KEY => Time::now()->getTimestamp(),
        ]);
    }

    /**
     * Akhiri sesi login sepenuhnya.
     */
    protected function endAuthSession(): void
    {
        session()->remove(self::AUTH_SESSION_KEYS);
        session()->destroy();
    }

    /**
     * Kembali ke form login dengan pesan dan HANYA identifier (NISN/NIP/username)
     * sebagai isian ulang. Sengaja tidak memakai withInput() karena itu menyalin
     * seluruh POST (termasuk kode unik/password) ke session.
     *
     * Stage 9: login pemilih dua tahap mengirim form lewat fetch (AJAX); pesan
     * yang sama dikirim sebagai JSON. "step" = tahap form yang perlu diperbaiki
     * (1 = identitas, 2 = kode unik). Isinya sama dengan pesan flash, jadi
     * tidak membocorkan apakah NISN/NIP terdaftar.
     *
     * @param array<string, string>|string $message
     */
    protected function failLogin(string $field, string $value, array|string $message, int $status = 422): RedirectResponse|ResponseInterface
    {
        if ($this->wantsLoginJson()) {
            return $this->response->setStatusCode($status)->setJSON([
                'ok'       => false,
                'step'     => is_array($message) && isset($message[$field]) ? 1 : 2,
                'messages' => array_values((array) $message),
            ]);
        }

        $response = redirect()->back()->with('old_' . $field, $value);

        return is_array($message)
            ? $response->with('errors', $message)
            : $response->with('error', $message);
    }

    /**
     * Login berhasil: redirect biasa, atau JSON berisi tujuan untuk login
     * dua tahap (animasi gembok terbuka dulu, baru pindah halaman).
     */
    protected function loginSucceeded(string $path): RedirectResponse|ResponseInterface
    {
        if ($this->wantsLoginJson()) {
            return $this->response->setJSON(['ok' => true, 'redirect' => site_url($path)]);
        }

        return redirect()->to($path);
    }

    protected function wantsLoginJson(): bool
    {
        return $this->request->isAJAX()
            || str_contains(strtolower($this->request->getHeaderLine('Accept')), 'application/json');
    }

    /**
     * Sisa detik tunggu bila login untuk akun/IP ini sedang diblokir,
     * atau 0 bila boleh mencoba. Pengecekan ini tidak mengurangi kuota.
     */
    protected function loginBlockedSeconds(string $scope, string $identifier): int
    {
        $throttler = service('throttler');

        foreach ($this->throttleBuckets($scope, $identifier) as [$key, $capacity, $seconds]) {
            if (! $throttler->check($key, $capacity, $seconds, 0)) {
                return max(1, $throttler->getTokenTime());
            }
        }

        return 0;
    }

    /**
     * Catat satu login gagal pada kuota akun dan kuota IP.
     */
    protected function recordLoginFailure(string $scope, string $identifier): void
    {
        $throttler = service('throttler');

        foreach ($this->throttleBuckets($scope, $identifier) as [$key, $capacity, $seconds]) {
            $throttler->check($key, $capacity, $seconds);
        }
    }

    /**
     * Pulihkan kuota akun setelah login berhasil (kuota IP tidak direset).
     */
    protected function clearLoginFailures(string $scope, string $identifier): void
    {
        service('throttler')->remove($this->throttleBuckets($scope, $identifier)[0][0]);
    }

    /**
     * Key cache di-hash karena IPv6 (::1) dan input user bisa mengandung
     * karakter yang dilarang sebagai key cache CodeIgniter ({}()/\@:).
     *
     * @return list<array{0: string, 1: int, 2: int}>
     */
    private function throttleBuckets(string $scope, string $identifier): array
    {
        return [
            [
                'login_' . $scope . '_acct_' . sha1(strtolower($identifier)),
                self::THROTTLE_ACCOUNT_CAPACITY,
                self::THROTTLE_ACCOUNT_SECONDS,
            ],
            [
                'login_' . $scope . '_ip_' . sha1($this->request->getIPAddress()),
                self::THROTTLE_IP_CAPACITY,
                self::THROTTLE_IP_SECONDS,
            ],
        ];
    }
}
