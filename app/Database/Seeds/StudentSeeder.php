<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class StudentSeeder extends Seeder
{
    /**
     * Seed data siswa CONTOH (bukan data nyata).
     * NISN contoh sengaja berpola 00000000xx agar mudah dikenali sebagai sample
     * dan sekaligus menguji leading zero. Kode unik = tanggal lahir DDMMYYYY.
     * Siswa 0000000012 non-aktif untuk menguji penolakan login.
     */
    public function run()
    {
        $existing = $this->db->table('students')->countAllResults();

        if ($existing > 0) {
            return;
        }

        $now = Time::now()->toDateTimeString();

        $students = [
            ['nisn' => '0000000001', 'name' => 'Ahmad Fauzan',      'jenis_kelamin' => 'L', 'kelas' => '7A', 'nomor_absen' => 1,    'kodeunik' => '05062013', 'status_aktif' => 1],
            ['nisn' => '0000000002', 'name' => 'Bunga Larasati',    'jenis_kelamin' => 'P', 'kelas' => '7A', 'nomor_absen' => 2,    'kodeunik' => '17092013', 'status_aktif' => 1],
            ['nisn' => '0000000003', 'name' => 'Candra Setiawan',   'jenis_kelamin' => 'L', 'kelas' => '7B', 'nomor_absen' => 1,    'kodeunik' => '01032013', 'status_aktif' => 1],
            ['nisn' => '0000000004', 'name' => 'Dinda Permatasari', 'jenis_kelamin' => 'P', 'kelas' => '7B', 'nomor_absen' => 2,    'kodeunik' => '23112013', 'status_aktif' => 1],
            ['nisn' => '0000000005', 'name' => 'Eko Ramadhan',      'jenis_kelamin' => 'L', 'kelas' => '8A', 'nomor_absen' => 1,    'kodeunik' => '09042012', 'status_aktif' => 1],
            ['nisn' => '0000000006', 'name' => 'Fitria Anjani',     'jenis_kelamin' => 'P', 'kelas' => '8A', 'nomor_absen' => 2,    'kodeunik' => '30072012', 'status_aktif' => 1],
            ['nisn' => '0000000007', 'name' => 'Galih Prasetyo',    'jenis_kelamin' => 'L', 'kelas' => '8B', 'nomor_absen' => 1,    'kodeunik' => '14012012', 'status_aktif' => 1],
            ['nisn' => '0000000008', 'name' => 'Hana Wulandari',    'jenis_kelamin' => 'P', 'kelas' => '8B', 'nomor_absen' => 2,    'kodeunik' => '02082012', 'status_aktif' => 1],
            ['nisn' => '0000000009', 'name' => 'Irfan Maulana',     'jenis_kelamin' => 'L', 'kelas' => '9A', 'nomor_absen' => 1,    'kodeunik' => '19052011', 'status_aktif' => 1],
            ['nisn' => '0000000010', 'name' => 'Jihan Aulia',       'jenis_kelamin' => 'P', 'kelas' => '9A', 'nomor_absen' => 2,    'kodeunik' => '27102011', 'status_aktif' => 1],
            ['nisn' => '0000000011', 'name' => 'Krisna Hadinata',   'jenis_kelamin' => 'L', 'kelas' => '9B', 'nomor_absen' => null, 'kodeunik' => '11062011', 'status_aktif' => 1],
            ['nisn' => '0000000012', 'name' => 'Larasati Putri',    'jenis_kelamin' => 'P', 'kelas' => '9B', 'nomor_absen' => 2,    'kodeunik' => '08032011', 'status_aktif' => 0],
        ];

        foreach ($students as &$student) {
            $student['created_at'] = $now;
            $student['updated_at'] = $now;
        }
        unset($student);

        $this->db->table('students')->insertBatch($students);
    }
}
