<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class AdminSeeder extends Seeder
{
    /**
     * Seed 1 akun admin development: username "admin", password "admin123".
     * Kredensial ini HANYA untuk pengembangan lokal, wajib diganti sebelum dipakai.
     */
    public function run()
    {
        $existing = $this->db->table('admins')->where('username', 'admin')->get()->getRow();

        if ($existing) {
            return;
        }

        $now = Time::now()->toDateTimeString();

        $this->db->table('admins')->insert([
            'name'          => 'Administrator OSIS (Dev)',
            'username'      => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'created_at'    => $now,
            'updated_at'    => $now,
        ]);
    }
}
