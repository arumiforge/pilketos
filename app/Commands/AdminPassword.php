<?php

namespace App\Commands;

use App\Libraries\AdminAccount;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use InvalidArgumentException;

/**
 * php spark admin:password <nama_pengguna>
 *
 * Mengganti kata sandi admin dengan kata sandi acak baru (Stage 4), mis. bila
 * lupa atau untuk mengganti admin development sebelum hari pemilihan.
 */
class AdminPassword extends BaseCommand
{
    protected $group       = 'Pemilihan OSIS';
    protected $name        = 'admin:password';
    protected $description = 'Mengganti kata sandi admin dengan kata sandi acak baru (ditampilkan sekali).';
    protected $usage       = 'admin:password <nama_pengguna>';
    protected $arguments   = [
        'nama_pengguna' => 'Nama pengguna admin yang kata sandinya diganti.',
    ];

    public function run(array $params)
    {
        $username = (string) ($params[0] ?? '');

        if ($username === '') {
            $username = CLI::prompt('Nama pengguna', null, 'required'); // @codeCoverageIgnore
        }

        try {
            $password = (new AdminAccount())->resetPassword($username);
        } catch (InvalidArgumentException $e) {
            CLI::error($e->getMessage());

            return EXIT_ERROR;
        }

        CLI::write('Kata sandi admin "' . strtolower(trim($username)) . '" diganti.', 'green');
        CLI::write('Kata sandi baru: ' . $password);
        CLI::write('Kata sandi hanya ditampilkan sekali. Simpan di tempat aman.');

        return EXIT_SUCCESS;
    }
}
