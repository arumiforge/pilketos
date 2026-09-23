<?php

namespace App\Controllers;

use App\Libraries\CandidateTheme;
use App\Models\CandidateModel;
use App\Models\ElectionModel;
use App\Services\PublicLiveCount;
use CodeIgniter\Exceptions\PageNotFoundException;
use Config\Homepage;

/**
 * Beranda imersif (redesign beranda, STAGE5-NOTES.md): layar pembuka, scene
 * layar penuh (hero, pintu masuk Siswa/Guru, perolehan suara), panel status
 * bergaya terminal, dan live count publik.
 */
class Home extends BaseController
{
    public function index()
    {
        $config   = config(Homepage::class);
        $election = model(ElectionModel::class)->getCurrentElection();
        $themes   = [];
        $active   = [];

        foreach (model(CandidateModel::class)->getAllOrdered() as $row) {
            $theme                = CandidateTheme::present($row);
            $themes[$theme['id']] = $theme;

            if ((int) $row['status_aktif'] === 1) {
                $active[] = $theme;
            }
        }

        $live = null;

        if ($config->publicLiveCount) {
            $live = PublicLiveCount::build($election, $config);
            // Pasangan yang dihitung (aktif + nonaktif yang sudah punya suara
            // sah) dengan tema & foto, agar persentase di layar berjumlah 100%.
            $live['pairs'] = [];

            foreach ($live['candidates'] as $count) {
                if (isset($themes[$count['id']])) {
                    $live['pairs'][] = $themes[$count['id']] + ['percent' => $count['percent']];
                }
            }
        }

        $html = view('home/index', [
            'election'   => $election,
            // Teaser publik: hanya nomor, nama, foto, dan warna tema.
            'candidates' => $active,
            'live'       => $live,
            'home'       => $config,
            'immersive'  => true,
            'bodyClass'  => 'page-home',
        ]);

        // Data renderer view bersifat bersama (Config\View::$saveData): penanda
        // beranda imersif tidak boleh terbawa ke render berikutnya di proses
        // yang sama (test otomatis, worker mode).
        service('renderer')->resetData();

        return $html;
    }

    /**
     * GET live-count: angka live count publik untuk beranda (JSON, no-store).
     * Hanya persentase per pasangan + partisipasi (PublicLiveCount); 404 bila
     * Config\Homepage::$publicLiveCount = false.
     */
    public function liveCount()
    {
        $config = config(Homepage::class);

        if (! $config->publicLiveCount) {
            throw PageNotFoundException::forPageNotFound();
        }

        $election = model(ElectionModel::class)->getCurrentElection();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON(PublicLiveCount::build($election, $config));
    }
}
