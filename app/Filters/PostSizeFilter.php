<?php

namespace App\Filters;

use App\Libraries\CandidateAssets;
use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\IncomingRequest;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Unggahan yang melebihi post_max_size (Stage 3).
 *
 * Bila total body POST melebihi post_max_size, PHP membuang SELURUH isi
 * POST dan $_FILES, termasuk token CSRF, sehingga admin akan melihat pesan
 * "sesi kedaluwarsa" yang membingungkan. Filter global ini berjalan SEBELUM
 * CSRF dan mengembalikan pesan yang jelas. Tidak melewati pemeriksaan apa
 * pun: request tetap ditolak, hanya pesannya yang tepat.
 */
class PostSizeFilter implements FilterInterface
{
    public function before(RequestInterface $request, $arguments = null)
    {
        if (! $request instanceof IncomingRequest || $request->getMethod() !== 'POST') {
            return null;
        }

        $limit  = CandidateAssets::iniBytes((string) ini_get('post_max_size'));
        $length = (int) $request->getServer('CONTENT_LENGTH');

        if ($limit <= 0 || $length <= $limit) {
            return null;
        }

        $message = sprintf(
            'Total ukuran unggahan (%s) melebihi batas server (%s). Kurangi jumlah atau ukuran file, lalu kirim ulang.',
            CandidateAssets::formatBytes($length),
            CandidateAssets::formatBytes($limit),
        );

        if ($request->isAJAX() || str_contains(strtolower($request->getHeaderLine('Accept')), 'application/json')) {
            return service('response')->setStatusCode(413)->setJSON(['status' => 'too_large', 'message' => $message]);
        }

        return redirect()->back()->with('error', $message);
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        return null;
    }
}
