<?php

namespace Tests\Support;

use CodeIgniter\Config\Services;

/**
 * Simulasi unggahan file HTTP pada feature test (Stage 3).
 *
 * PHP hanya menganggap file hasil unggahan HTTP sungguhan sebagai
 * is_uploaded_file(). Test mendaftarkan file buatannya di sini;
 * tests/_support/bootstrap.php mengganti is_uploaded_file() KHUSUS di
 * namespace CodeIgniter\HTTP\Files (UploadedFile::isValid()) agar hanya
 * file yang terdaftar dianggap unggahan sah. Kode aplikasi tidak diubah.
 */
final class UploadFixture
{
    /**
     * @var array<string, true>
     */
    private static array $registered = [];

    public static function isRegistered(string $path): bool
    {
        return isset(self::$registered[realpath($path) ?: $path]);
    }

    /**
     * Tetapkan $_FILES untuk request berikutnya.
     *
     * @param array<string, array{path: string, name: string, type?: string, error?: int}> $files field => file
     */
    public static function attach(array $files): void
    {
        $array = [];

        foreach ($files as $field => $file) {
            $path = $file['path'];
            self::$registered[realpath($path) ?: $path] = true;

            $array[$field] = [
                'name'      => $file['name'],
                'full_path' => $file['name'],
                'type'      => $file['type'] ?? 'application/octet-stream',
                'tmp_name'  => $path,
                'error'     => $file['error'] ?? UPLOAD_ERR_OK,
                'size'      => is_file($path) ? filesize($path) : 0,
            ];
        }

        Services::superglobals()->setFilesArray($array);
    }

    public static function reset(): void
    {
        self::$registered = [];
        Services::superglobals()->setFilesArray([]);
    }
}
