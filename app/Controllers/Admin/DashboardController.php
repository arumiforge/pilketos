<?php

namespace App\Controllers\Admin;

use App\Services\FinalResult;

/**
 * Dasbor admin (Stage 3): status & jadwal election, ringkasan pemilih,
 * suara per pasangan, rekap jenjang & kelas. Angka awal dirender server
 * (tetap lengkap tanpa JavaScript); admin-live.js memperbaruinya dari
 * endpoint live count tanpa memuat ulang halaman.
 *
 * Stage 4: saat pemilihan FINISHED dasbor menampilkan keadaan final
 * (pasangan terpilih / seri / tanpa suara) dan tautan ke halaman hasil akhir.
 */
class DashboardController extends AdminController
{
    public function index()
    {
        $election = $this->election();
        $snapshot = service('analytics')->snapshot($election);

        return $this->render('admin/dashboard', [
            'title'    => 'Dasbor',
            'snapshot' => $snapshot,
            'final'    => FinalResult::build($election, $snapshot),
        ], 'dashboard');
    }
}
