<?php

namespace App\Controllers\Admin;

use App\Models\ElectionModel;
use App\Services\AnalyticsService;
use CodeIgniter\I18n\Time;

/**
 * Endpoint AJAX live count (MASTER section 15, Stage 3).
 *
 * GET admin/live-count -> JSON angka dasbor/analitik (ringkasan, suara &
 * persentase per pasangan, rekap jenis pemilih, jenis kelamin, kelas,
 * jenjang). Hanya admin (filter adminauth; AJAX tanpa sesi = 401 JSON).
 *
 * poll.interval (detik) memberi tahu admin-live.js kapan meminta lagi:
 * 10 detik saat ONGOING, 60 detik saat UPCOMING, 0 = berhenti (FINISHED
 * atau belum ada jadwal). Browser juga berhenti saat tab tidak aktif.
 */
class LiveCountController extends AdminController
{
    public const INTERVAL_ONGOING  = 10;
    public const INTERVAL_UPCOMING = 60;

    public function index()
    {
        $election = $this->election();
        $snapshot = AnalyticsService::toJson(service('analytics')->snapshot($election));
        $now      = Time::now();

        return $this->response
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setJSON($snapshot + [
                'generated_label' => $now->toLocalizedString('HH.mm.ss') . ' WIB',
                'server_time'     => $now->getTimestamp() * 1000,
                'poll'            => ['interval' => self::interval($election['status'] ?? null)],
            ]);
    }

    public static function interval(?string $status): int
    {
        return match ($status) {
            ElectionModel::STATUS_ONGOING  => self::INTERVAL_ONGOING,
            ElectionModel::STATUS_UPCOMING => self::INTERVAL_UPCOMING,
            default                        => 0,
        };
    }
}
