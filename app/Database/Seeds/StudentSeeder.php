<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class StudentSeeder extends Seeder
{
    /**
     * Seed data siswa contoh (data pengembangan, bukan data nyata).
     * NISN dan kodeunik disimpan sebagai string agar leading zero terjaga.
     */
    public function run()
    {
        $existing = $this->db->table('students')->countAllResults();

        if ($existing > 0) {
            return;
        }

        $now = date('Y-m-d H:i:s');

        $students = [
            ['nisn' => '0081234561', 'name' => 'Ahmad Fauzan',      'jenis_kelamin' => 'L', 'kelas' => '7A', 'nomor_absen' => 1,  'kodeunik' => '05062013'],
            ['nisn' => '0081234562', 'name' => 'Bunga Larasati',    'jenis_kelamin' => 'P', 'kelas' => '7A', 'nomor_absen' => 2,  'kodeunik' => '17092013'],
            ['nisn' => '0081234563', 'name' => 'Candra Setiawan',   'jenis_kelamin' => 'L', 'kelas' => '7B', 'nomor_absen' => 1,  'kodeunik' => '01032013'],
            ['nisn' => '0081234564', 'name' => 'Dinda Permatasari', 'jenis_kelamin' => 'P', 'kelas' => '7B', 'nomor_absen' => 2,  'kodeunik' => '23112013'],
            ['nisn' => '0081234565', 'name' => 'Eko Ramadhan',      'jenis_kelamin' => 'L', 'kelas' => '8A', 'nomor_absen' => 1,  'kodeunik' => '09042012'],
            ['nisn' => '0081234566', 'name' => 'Fitria Anjani',     'jenis_kelamin' => 'P', 'kelas' => '8A', 'nomor_absen' => 2,  'kodeunik' => '30072012'],
            ['nisn' => '0081234567', 'name' => 'Galih Prasetyo',    'jenis_kelamin' => 'L', 'kelas' => '8B', 'nomor_absen' => 1,  'kodeunik' => '14012012'],
            ['nisn' => '0081234568', 'name' => 'Hana Wulandari',    'jenis_kelamin' => 'P', 'kelas' => '8B', 'nomor_absen' => 2,  'kodeunik' => '02082012'],
            ['nisn' => '0081234569', 'name' => 'Irfan Maulana',     'jenis_kelamin' => 'L', 'kelas' => '9A', 'nomor_absen' => 1,  'kodeunik' => '19052011'],
            ['nisn' => '0081234570', 'name' => 'Jihan Aulia',       'jenis_kelamin' => 'P', 'kelas' => '9A', 'nomor_absen' => 2,  'kodeunik' => '27102011'],
            ['nisn' => '0081234571', 'name' => 'Krisna Hadinata',   'jenis_kelamin' => 'L', 'kelas' => '9B', 'nomor_absen' => 1,  'kodeunik' => '11062011'],
            ['nisn' => '0081234572', 'name' => 'Larasati Putri',    'jenis_kelamin' => 'P', 'kelas' => '9B', 'nomor_absen' => 2,  'kodeunik' => '08032011'],
        ];

        foreach ($students as &$student) {
            $student['created_at'] = $now;
            $student['updated_at'] = $now;
        }
        unset($student);

        $this->db->table('students')->insertBatch($students);
    }
}
