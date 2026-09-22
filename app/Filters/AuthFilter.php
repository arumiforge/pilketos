<?php

namespace App\Filters;

use App\Controllers\BaseController;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Dasar filter autentikasi per role.
 *
 * Request lolos hanya bila:
 * - sesi berisi user_type yang sesuai role, dan
 * - akun pada sesi masih ada & aktif di database (akun yang dihapus/
 *   dinonaktifkan langsung kehilangan akses).
 *
 * Request AJAX/JSON yang ditolak menerima 401 JSON (bukan redirect HTML),
 * dipakai oleh voting Stage 2 dan live count Stage 3.
 */
abstract class AuthFilter implements FilterInterface
{
    /**
     * @return 'admin'|'student'|'teacher'
     */
    abstract protected function userType(): string;

    abstract protected function loginPath(): string;

    abstract protected function deniedMessage(): string;

    abstract protected function accountIsValid(int $id): bool;

    public function before(RequestInterface $request, $arguments = null)
    {
        $session = session();
        $type    = $this->userType();
        $id      = (int) $session->get($type . '_id');

        if ($session->get('user_type') === $type && $id > 0) {
            if ($this->accountIsValid($id)) {
                return null;
            }

            // Akun sudah tidak valid: buang identitas sesi.
            $session->remove(BaseController::AUTH_SESSION_KEYS);
        }

        if ($this->wantsJson($request)) {
            return service('response')
                ->setStatusCode(401)
                ->setJSON([
                    'status'   => 'unauthenticated',
                    'message'  => $this->deniedMessage(),
                    'redirect' => site_url($this->loginPath()),
                ]);
        }

        return redirect()->to($this->loginPath())->with('error', $this->deniedMessage());
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
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
