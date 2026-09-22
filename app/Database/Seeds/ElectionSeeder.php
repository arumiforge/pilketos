<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class ElectionSeeder extends Seeder
{
    /**
     * Seed 1 election development 2026.
     * start_at/end_at dibuat relatif terhadap waktu seed dijalankan agar
     * election langsung berada dalam status ONGOING saat testing lokal.
     */
    public function run()
    {
        $existing = $this->db->table('elections')->where('tahun', 2026)->get()->getRow();

        if ($existing) {
            return;
        }

        $this->db->table('elections')->insert([
            'nama'       => 'Pemilihan Ketua dan Wakil Ketua OSIS SMP 1 Dawe',
            'tahun'      => 2026,
            'start_at'   => date('Y-m-d H:i:s', strtotime('-1 day')),
            'end_at'     => date('Y-m-d H:i:s', strtotime('+7 days')),
            'status'     => 'ONGOING',
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
    }
}
