<?php

namespace App\Controllers\Admin;

/**
 * Dasbor admin (Stage 3): status & jadwal election, ringkasan pemilih,
 * suara per pasangan, rekap jenjang & kelas. Angka awal dirender server
 * (tetap lengkap tanpa JavaScript); admin-live.js memperbaruinya dari
 * endpoint live count tanpa memuat ulang halaman.
 */
class DashboardController extends AdminController
{
    public function index()
    {
        return $this->render('admin/dashboard', [
            'title'    => 'Dasbor',
            'snapshot' => service('analytics')->snapshot($this->election()),
        ], 'dashboard');
    }
}
