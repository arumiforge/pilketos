<?php

namespace App\Controllers;

use CodeIgniter\Controller;
use CodeIgniter\HTTP\CLIRequest;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Config\Services;
use Psr\Log\LoggerInterface;

/**
 * Base Controller
 *
 * Semua controller aplikasi (public, student, teacher, admin) diturunkan
 * dari kelas ini. Berisi helper bersama, termasuk throttling percobaan login.
 */
abstract class BaseController extends Controller
{
    /**
     * @var CLIRequest|IncomingRequest
     */
    protected $request;

    protected $helpers = ['url', 'form'];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger)
    {
        parent::initController($request, $response, $logger);
    }

    /**
     * Batasi jumlah percobaan login per kunci (biasanya IP + tipe login)
     * untuk mencegah brute force. Mengembalikan true jika request masih diizinkan.
     */
    protected function loginAttemptAllowed(string $key, int $maxAttempts = 5, int $seconds = 60): bool
    {
        $throttler = Services::throttler();

        return $throttler->check($key, $maxAttempts, $seconds) !== false;
    }
}
