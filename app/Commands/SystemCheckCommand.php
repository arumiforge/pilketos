<?php

namespace App\Commands;

use App\Libraries\SystemCheck;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * php spark osis:check
 *
 * Daftar periksa kesiapan server (Stage 4): versi PHP & ekstensi, .env,
 * php.ini, folder tulis, koneksi & versi database, migration, trigger
 * integritas suara, admin, jadwal, pasangan calon, data pemilih.
 * Exit code 1 bila ada item GAGAL.
 */
class SystemCheckCommand extends BaseCommand
{
    protected $group       = 'Pemilihan OSIS';
    protected $name        = 'osis:check';
    protected $description = 'Memeriksa kesiapan server & data sebelum hari pemilihan.';
    protected $usage       = 'osis:check';

    public function run(array $params)
    {
        $results = (new SystemCheck())->run();
        $colors  = [SystemCheck::OK => 'green', SystemCheck::WARN => 'yellow', SystemCheck::FAIL => 'red'];

        CLI::write('Pemeriksaan kesiapan - Pemilihan OSIS SMP 1 DAWE 2026', 'white');
        CLI::newLine();

        foreach ($results as $row) {
            CLI::write(
                str_pad('[' . $row['status'] . ']', 13) . $row['item'] . ': ' . $row['detail'],
                $colors[$row['status']] ?? null,
            );
        }

        $counts = array_count_values(array_column($results, 'status'));
        CLI::newLine();
        CLI::write(sprintf(
            'Ringkasan: %d OK, %d peringatan, %d gagal.',
            $counts[SystemCheck::OK] ?? 0,
            $counts[SystemCheck::WARN] ?? 0,
            $counts[SystemCheck::FAIL] ?? 0,
        ));

        return SystemCheck::hasFailure($results) ? EXIT_ERROR : EXIT_SUCCESS;
    }
}
