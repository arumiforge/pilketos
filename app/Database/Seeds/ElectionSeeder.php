<?php

namespace App\Database\Seeds;

use App\Models\ElectionModel;
use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class ElectionSeeder extends Seeder
{
    /**
     * Seed 1 election development 2026.
     * Jadwal relatif terhadap waktu seed (mulai kemarin, selesai 7 hari lagi)
     * agar election langsung ONGOING saat testing lokal. Status dihitung
     * dari jadwal, bukan di-hardcode.
     */
    public function run()
    {
        $existing = $this->db->table('elections')->where('tahun', 2026)->get()->getRow();

        if ($existing) {
            return;
        }

        $now      = Time::now();
        $election = [
            'nama'     => 'Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 Dawe',
            'tahun'    => 2026,
            'start_at' => $now->subDays(1)->setSecond(0)->toDateTimeString(),
            'end_at'   => $now->addDays(7)->setSecond(0)->toDateTimeString(),
        ];
        $election['status']     = (new ElectionModel())->resolveStatus($election, $now);
        $election['created_at'] = $now->toDateTimeString();
        $election['updated_at'] = $now->toDateTimeString();

        $this->db->table('elections')->insert($election);
    }
}
