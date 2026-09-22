<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Jalankan seluruh seeder Stage 1 secara berurutan.
     * php spark db:seed DatabaseSeeder
     */
    public function run()
    {
        $this->call(AdminSeeder::class);
        $this->call(ElectionSeeder::class);
        $this->call(CandidateSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(TeacherSeeder::class);
    }
}
