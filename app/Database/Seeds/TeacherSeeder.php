<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class TeacherSeeder extends Seeder
{
    /**
     * Seed data guru contoh (data pengembangan, bukan data nyata).
     * NIP dan kodeunik disimpan sebagai string agar leading zero terjaga.
     */
    public function run()
    {
        $existing = $this->db->table('teachers')->countAllResults();

        if ($existing > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $teachers = [
            ['nip' => '198501012010011001', 'name' => 'Sudarmanto, S.Pd.',      'kodeunik' => '01011985', 'status_aktif' => 1],
            ['nip' => '198703152011012002', 'name' => 'Rina Kusumawati, S.Pd.', 'kodeunik' => '15031987', 'status_aktif' => 1],
            ['nip' => '199002202012011003', 'name' => 'Bambang Hartono, S.Pd.', 'kodeunik' => '20021990', 'status_aktif' => 1],
            ['nip' => '199206102013012004', 'name' => 'Siti Nur Aini, S.Pd.',   'kodeunik' => '10061992', 'status_aktif' => 1],
            ['nip' => '198812252014011005', 'name' => 'Yusuf Kurniawan, S.Pd.', 'kodeunik' => '25121988', 'status_aktif' => 1],
        ];

        foreach ($teachers as &$teacher) {
            $teacher['created_at'] = $now;
            $teacher['updated_at'] = $now;
        }
        unset($teacher);

        $this->db->table('teachers')->insertBatch($teachers);
    }
}
