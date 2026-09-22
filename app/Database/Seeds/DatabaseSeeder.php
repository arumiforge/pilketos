<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seluruh seeder DEVELOPMENT secara berurutan.
     * php spark db:seed DatabaseSeeder
     *
     * Semua data (admin, election, kandidat, siswa, guru) adalah contoh.
     * Ditolak di production agar data contoh tidak ikut terhitung sebagai pemilih.
     */
    public function run()
    {
        if (ENVIRONMENT === 'production') {
            throw new RuntimeException('DatabaseSeeder berisi data contoh dan tidak boleh dijalankan di production.');
        }

        $this->call(AdminSeeder::class);
        $this->call(ElectionSeeder::class);
        $this->call(CandidateSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(TeacherSeeder::class);
    }
}
