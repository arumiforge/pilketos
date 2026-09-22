<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class CandidateSeeder extends Seeder
{
    /**
     * Seed 3 pasangan calon contoh (data pengembangan, bukan data nyata).
     * Foto & asset tema sengaja dikosongkan (null) karena upload asset
     * ditangani oleh panel admin pada Stage 3.
     */
    public function run()
    {
        $existing = $this->db->table('candidates')->countAllResults();

        if ($existing > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $candidates = [
            [
                'nomor_urut'       => 1,
                'nama_ketua'       => 'Arka Wibisana',
                'nama_wakil'       => 'Naya Kirana',
                'foto_ketua'       => null,
                'foto_wakil'       => null,
                'visi'             => 'Mewujudkan OSIS yang aktif, terbuka, dan dekat dengan seluruh siswa.',
                'misi'             => "1. Menghidupkan kembali kegiatan ekstrakurikuler.\n2. Membuka kanal aspirasi siswa secara rutin.\n3. Memperkuat kegiatan literasi dan seni di sekolah.",
                'theme_name'       => 'Terracotta',
                'theme_background' => null,
                'theme_accent'     => '#C4432B',
                'theme_asset'      => null,
                'status_aktif'     => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'nomor_urut'       => 2,
                'nama_ketua'       => 'Bagas Prayoga',
                'nama_wakil'       => 'Citra Maheswari',
                'foto_ketua'       => null,
                'foto_wakil'       => null,
                'visi'             => 'OSIS sebagai wadah kolaborasi siswa yang disiplin dan berprestasi.',
                'misi'             => "1. Meningkatkan kualitas program akademik pendukung.\n2. Membangun budaya disiplin yang positif.\n3. Mendorong prestasi siswa di tingkat sekolah dan luar sekolah.",
                'theme_name'       => 'Deep Forest',
                'theme_background' => null,
                'theme_accent'     => '#2F5D50',
                'theme_asset'      => null,
                'status_aktif'     => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
            [
                'nomor_urut'       => 3,
                'nama_ketua'       => 'Dewi Anggraini',
                'nama_wakil'       => 'Fajar Nugroho',
                'foto_ketua'       => null,
                'foto_wakil'       => null,
                'visi'             => 'Membangun lingkungan sekolah yang inklusif, kreatif, dan peduli sesama.',
                'misi'             => "1. Mengadakan program kepedulian sosial rutin.\n2. Mewadahi kreativitas siswa lintas minat.\n3. Menjaga lingkungan sekolah yang ramah bagi semua siswa.",
                'theme_name'       => 'Midnight Navy',
                'theme_background' => null,
                'theme_accent'     => '#1B3A6B',
                'theme_asset'      => null,
                'status_aktif'     => 1,
                'created_at'       => $now,
                'updated_at'       => $now,
            ],
        ];

        $this->db->table('candidates')->insertBatch($candidates);
    }
}
