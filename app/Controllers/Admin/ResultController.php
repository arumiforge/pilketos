<?php

namespace App\Controllers\Admin;

use App\Libraries\CandidateTheme;
use App\Models\CandidateModel;
use App\Models\ElectionModel;
use App\Services\FinalResult;

/**
 * Halaman hasil akhir (Stage 4, MASTER section 17). Hanya admin.
 *
 * Hasil akhir aktif hanya bila pemilihan benar-benar selesai menurut jadwal
 * dan jam server (FINISHED, now >= end_at). Sebelum itu halaman hanya
 * menampilkan keadaan terkunci tanpa angka; angka berjalan tetap ada di
 * dasbor live count. Angka hasil akhir berasal dari AnalyticsService (definisi
 * yang sama dengan dasbor), dan confetti hanya dijalankan di halaman ini bila
 * ada satu pasangan terpilih.
 */
class ResultController extends AdminController
{
    /**
     * GET admin/results
     */
    public function index()
    {
        $election = $this->election();
        $finished = ($election['status'] ?? null) === ElectionModel::STATUS_FINISHED;
        $snapshot = $finished ? service('analytics')->snapshot($election) : null;

        return $this->render('admin/results/index', [
            'title'    => 'Hasil Akhir',
            'snapshot' => $snapshot,
            'result'   => FinalResult::build($election, $snapshot),
            'themes'   => $finished ? $this->themes() : [],
        ], 'results');
    }

    /**
     * Tema tampilan per pasangan (foto, pola, aksen) untuk panggung pemenang.
     *
     * @return array<int, array<string, mixed>>
     */
    private function themes(): array
    {
        $themes = [];

        foreach (model(CandidateModel::class)->getAllOrdered() as $candidate) {
            $themes[(int) $candidate['id']] = CandidateTheme::present($candidate);
        }

        return $themes;
    }
}
