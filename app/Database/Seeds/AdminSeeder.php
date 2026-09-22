<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Seed 1 akun admin development.
     * Kredensial ini HANYA untuk pengembangan lokal, wajib diganti sebelum production.
     */
    public function run()
    {
        $existing = $this->db->table('admins')->where('username', 'admin')->get()->getRow();

        if ($existing) {
            return;
        }

        $this->db->table('admins')->insert([
            'name'          => 'Administrator OSIS',
            'username'      => 'admin',
            'password_hash' => password_hash('admin123', PASSWORD_DEFAULT),
            'created_at'    => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ]);
    }
}
