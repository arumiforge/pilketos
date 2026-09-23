<?php

namespace App\Commands;

use App\Libraries\AdminAccount;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use InvalidArgumentException;

/**
 * php spark admin:create --username panitia --name "Panitia Pemilihan OSIS"
 *
 * Membuat admin production (Stage 4). Kata sandi acak ditampilkan SEKALI;
 * simpan di tempat aman. Boleh dijalankan saat CI_ENVIRONMENT = production.
 */
class AdminCreate extends BaseCommand
{
    protected $group       = 'Pemilihan OSIS';
    protected $name        = 'admin:create';
    protected $description = 'Membuat akun admin dengan kata sandi acak (ditampilkan sekali).';
    protected $usage       = 'admin:create --username <nama_pengguna> --name "<nama lengkap>"';
    protected $options     = [
        '--username' => 'Nama pengguna login (3-50 karakter: huruf kecil, angka, . _ -).',
        '--name'     => 'Nama admin yang tampil di panel dan audit log.',
    ];

    public function run(array $params)
    {
        $username = (string) ($params['username'] ?? '');
        $name     = (string) ($params['name'] ?? '');

        if ($username === '') {
            $username = CLI::prompt('Nama pengguna', null, 'required'); // @codeCoverageIgnore
        }

        if ($name === '') {
            $name = CLI::prompt('Nama admin', null, 'required'); // @codeCoverageIgnore
        }

        try {
            $account = (new AdminAccount())->create($name, $username);
        } catch (InvalidArgumentException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Admin "' . $account['username'] . '" dibuat.', 'green');
        CLI::write('Kata sandi: ' . $account['password']);
        CLI::write('Kata sandi hanya ditampilkan sekali. Simpan di tempat aman; untuk mengganti: php spark admin:password ' . $account['username']);

        return EXIT_SUCCESS;
    }
}
