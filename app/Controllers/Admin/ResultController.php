<?php

namespace App\Controllers\Admin;

use App\Libraries\CandidateTheme;
use App\Libraries\ResultPdf;
use App\Models\CandidateModel;
use App\Models\ElectionModel;
use App\Services\FinalResult;
use CodeIgniter\HTTP\RedirectResponse;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * Halaman hasil akhir (Stage 4, MASTER section 17). Hanya admin.
 *
 * Hasil akhir aktif hanya bila pemilihan benar-benar selesai menurut jadwal
 * dan jam server (FINISHED, now >= end_at). Sebelum itu halaman hanya
 * menampilkan keadaan terkunci tanpa angka; angka berjalan tetap ada di
 * dasbor live count. Angka hasil akhir berasal dari AnalyticsService (definisi
 * yang sama dengan dasbor), dan confetti hanya dijalankan di halaman ini bila
 * ada satu pasangan terpilih.
 *
 * Stage 13: tombol "Cetak PDF" membuka admin/hasil/cetak, PDF A4 yang dibuat
 * server dengan dompdf (App\Libraries\ResultPdf) sehingga hasil cetak sama di
 * semua browser: logo sekolah di atas, rekap di halaman kedua, catatan kaki
 * dan nomor halaman di setiap halaman.
 */
class ResultController extends AdminController
{
    /**
     * GET admin/hasil
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
     * GET admin/hasil/cetak
     * PDF hasil akhir (inline: dibuka di penampil PDF browser untuk dicetak
     * atau disimpan). Hanya saat pemilihan sudah selesai.
     */
    public function pdf(): RedirectResponse|ResponseInterface
    {
        $election = $this->election();

        if (($election['status'] ?? null) !== ElectionModel::STATUS_FINISHED) {
            return redirect()->to('admin/hasil')->with('error', 'PDF hasil akhir tersedia setelah pemilihan selesai.');
        }

        $snapshot = service('analytics')->snapshot($election);
        $result   = FinalResult::build($election, $snapshot);

        $html = view('admin/results/pdf', [
            'election' => $election,
            'snapshot' => $snapshot,
            'result'   => $result,
            'logo'     => ResultPdf::imageData(FCPATH . 'assets/img/brand/logo-smp1dawe.png', 360),
            'photos'   => $result['winner'] === null ? [] : $this->photos((int) $result['winner']['id']),
        ], ['debug' => false]);

        $filename = 'hasil-akhir-pilketos-' . preg_replace('/\D/', '', (string) $election['tahun']) . '.pdf';

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
            ->setHeader('Cache-Control', 'private, no-store, max-age=0')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody((new ResultPdf())->render($html));
    }

    /**
     * Foto pasangan terpilih untuk PDF (data URI, sudah diperkecil).
     *
     * @return array<int, array{hero: string|null, ketua: string|null, wakil: string|null}>
     */
    private function photos(int $candidateId): array
    {
        $candidate = model(CandidateModel::class)->find($candidateId);
        if ($candidate === null) {
            return [];
        }

        $dir  = FCPATH . CandidateModel::UPLOAD_DIR . DIRECTORY_SEPARATOR;
        $file = static fn (mixed $name): ?string => is_string($name) && $name !== '' ? $dir . basename($name) : null;

        return [$candidateId => [
            'hero'  => ResultPdf::imageData($file($candidate['theme_asset']['hero'] ?? null), 900, 4 / 3),
            'ketua' => ResultPdf::imageData($file($candidate['foto_ketua'] ?? null), 640, 4 / 5),
            'wakil' => ResultPdf::imageData($file($candidate['foto_wakil'] ?? null), 640, 4 / 5),
        ]];
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
