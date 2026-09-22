<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class TeacherSeeder extends Seeder
{
    /**
     * Seed data guru CONTOH (bukan data nyata).
     * NIP contoh sengaja berpola 0000...0x agar mudah dikenali sebagai sample
     * dan sekaligus menguji leading zero. Kode unik = tanggal lahir DDMMYYYY.
     * Guru 000000000000000005 non-aktif untuk menguji penolakan login.
     */
    public function run()
    {
        $existing = $this->db->table('teachers')->countAllResults();

        if ($existing > 0) {
            return;
        }

        $now = Time::now()->toDateTimeString();

        $teachers = [
            ['nip' => '000000000000000001', 'name' => 'Sudarmanto, S.Pd.',      'kodeunik' => '01011985', 'status_aktif' => 1],
            ['nip' => '000000000000000002', 'name' => 'Rina Kusumawati, S.Pd.', 'kodeunik' => '15031987', 'status_aktif' => 1],
            ['nip' => '000000000000000003', 'name' => 'Bambang Hartono, S.Pd.', 'kodeunik' => '20021990', 'status_aktif' => 1],
            ['nip' => '000000000000000004', 'name' => 'Siti Nur Aini, S.Pd.',   'kodeunik' => '01061992', 'status_aktif' => 1],
            ['nip' => '000000000000000005', 'name' => 'Yusuf Kurniawan, S.Pd.', 'kodeunik' => '25121988', 'status_aktif' => 0],
        ];

        foreach ($teachers as &$teacher) {
            $teacher['created_at'] = $now;
            $teacher['updated_at'] = $now;
        }
        unset($teacher);

        $this->db->table('teachers')->insertBatch($teachers);
    }
}
