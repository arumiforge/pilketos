<?php

namespace App\Filters;

use CodeIgniter\Filters\FilterInterface;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Header keamanan tambahan untuk semua respons aplikasi (Stage 4).
 *
 * Melengkapi filter bawaan "secureheaders" (X-Frame-Options SAMEORIGIN,
 * X-Content-Type-Options nosniff, Referrer-Policy same-origin, ...) dan
 * Content-Security-Policy (Config\ContentSecurityPolicy):
 * - Permissions-Policy: kamera, mikrofon, lokasi, pembayaran, USB dimatikan;
 *   layar penuh hanya untuk situs ini (halaman hasil akhir di proyektor);
 * - Cross-Origin-Opener-Policy / Cross-Origin-Resource-Policy same-origin;
 * - Cache-Control no-store untuk setiap respons yang belum mengaturnya
 *   (komputer lab dipakai bergantian: halaman pilihan siswa sebelumnya tidak
 *   boleh muncul dari cache/tombol Back setelah keluar);
 * - X-Powered-By (versi PHP) dihapus agar versi server tidak diumumkan.
 */
class SecurityHeadersFilter implements FilterInterface
{
    public const PERMISSIONS_POLICY = 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), fullscreen=(self)';

    public function before(RequestInterface $request, $arguments = null)
    {
        return null;
    }

    public function after(RequestInterface $request, ResponseInterface $response, $arguments = null)
    {
        $response->setHeader('Permissions-Policy', self::PERMISSIONS_POLICY);
        $response->setHeader('Cross-Origin-Opener-Policy', 'same-origin');
        $response->setHeader('Cross-Origin-Resource-Policy', 'same-origin');

        if (! $response->hasHeader('Cache-Control')) {
            $response->setHeader('Cache-Control', 'no-store, max-age=0');
        }

        if (! headers_sent()) {
            header_remove('X-Powered-By');
        }

        return $response;
    }
}
