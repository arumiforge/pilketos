<?php

namespace App\Filters;

use App\Controllers\BaseController;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use CodeIgniter\I18n\Time;
use CodeIgniter\Session\SessionInterface;

/**
 * Dasar filter autentikasi per role.
 *
 * Request lolos hanya bila:
 * - sesi berisi user_type yang sesuai role, dan
 * - akun pada sesi masih ada & aktif di database (akun yang dihapus/
 *   dinonaktifkan langsung kehilangan akses), dan
 * - (Stage 4) sesi belum melewati batas idle role tersebut.
 *
 * Request AJAX/JSON yang ditolak menerima 401 JSON (bukan redirect HTML),
 * dipakai oleh voting Stage 2 dan live count Stage 3.
 */
abstract class AuthFilter implements FilterInterface
{
    public const IDLE_MESSAGE = 'Sesi berakhir karena tidak ada aktivitas. Silakan masuk kembali.';

    /**
     * Batas idle sesi siswa & guru (Stage 4): 15 menit tanpa request.
     */
    public const VOTER_IDLE_SECONDS = 900;

    /**
     * @return 'admin'|'student'|'teacher'
     */
    abstract protected function userType(): string;

    abstract protected function loginPath(): string;

    abstract protected function deniedMessage(): string;

    abstract protected function accountIsValid(int $id): bool;

    /**
     * Batas idle sesi dalam detik (0 = hanya batas sesi 2 jam dari Config\Session).
     * Pemilih memakai batas pendek (komputer lab/HP pinjaman yang ditinggal
     * tanpa "Keluar" tidak dapat dipakai orang lain untuk mencoblos).
     */
    protected function idleSeconds(): int
    {
        return 0;
    }

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $type    = $this->userType();
        $id      = (int) $session->get($type . '_id');
        $message = $this->deniedMessage();

        if ($session->get('user_type') === $type && $id > 0) {
            if ($this->idleExpired($session)) {
                $message = self::IDLE_MESSAGE;
            } elseif ($this->accountIsValid($id)) {
                $session->set(BaseController::AUTH_SEEN_KEY, Time::now()->getTimestamp());

                return null;
            }

            // Akun tidak valid lagi / sesi ditinggal terlalu lama: buang identitas sesi.
            $session->remove(BaseController::AUTH_SESSION_KEYS);
        }

        if ($this->wantsJson($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 'unauthenticated',
                    'message'  => $message,
                    'redirect' => site_url($this->loginPath()),
                ]);
        }

        return redirect()->to($this->loginPath())->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }

    private function idleExpired(SessionInterface $session): bool
    {
        $limit = $this->idleSeconds();
        $seen  = $session->get(BaseController::AUTH_SEEN_KEY);

        return $limit > 0 && is_int($seen) && Time::now()->getTimestamp() - $seen > $limit;
    }

    private function wantsJson(RequestInterface $request): bool
    {
        if (! $request instanceof IncomingRequest) {
            return false;
        }

        return $request->isAJAX()
            || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json');
    }
}
