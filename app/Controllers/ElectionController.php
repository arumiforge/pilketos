<?php

namespace App\Controllers;

use App\Models\ElectionModel;

/**
 * Endpoint publik jam pemilihan untuk countdown.
 *
 * Dipanggil countdown.js hanya saat hitungan mencapai nol atau tab aktif
 * kembali (bukan polling terus-menerus). Tidak berisi data pemilih/suara.
 */
class ElectionController extends BaseController
{
    /**
     * GET jam-server
     */
    public function clock()
    {
        $election = model(ElectionModel::class)->getCurrentElection();
        $clock    = election_clock($election);

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON($clock + ['label' => election_status_label($clock['status'])]);
    }
}
