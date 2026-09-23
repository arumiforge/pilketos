<?php

/**
 * Bootstrap PHPUnit aplikasi.
 *
 * Mendefinisikan pengganti is_uploaded_file() di namespace
 * CodeIgniter\HTTP\Files SEBELUM kelas apa pun dimuat, sehingga
 * UploadedFile::isValid() pada feature test menerima file yang didaftarkan
 * Tests\Support\UploadFixture (lihat kelas tersebut). Lalu bootstrap
 * bawaan CodeIgniter dijalankan seperti biasa.
 */

namespace CodeIgniter\HTTP\Files {
    function is_uploaded_file(string $filename): bool
    {
        return \Tests\Support\UploadFixture::isRegistered($filename);
    }
}

namespace {
    require __DIR__ . '/../../system/Test/bootstrap.php';
}
