<?php

namespace App\Services;

use App\Models\ElectionModel;
use CodeIgniter\I18n\Time;
use Config\Homepage;

/**
 * Live count publik di beranda (redesign beranda, STAGE5-NOTES.md).
 *
 * Proyeksi SEMPIT dari AnalyticsService::snapshot(), sehingga angkanya satu
 * definisi dengan dasbor admin (suara sah = LOCKED milik pemilih aktif):
 * - persentase suara sah per pasangan (urut nomor, bukan peringkat);
 * - partisipasi keseluruhan: sudah memilih / seluruh pemilih aktif.
 * Tidak ada identitas pemilih, jumlah suara per pasangan, pemisahan siswa
 * vs guru, maupun rekap kelas/jenjang/jenis kelamin: rincian itu tetap
 * hanya di panel admin (admin/live-count, analitik).
 */
final class PublicLiveCount
{
    /**
     * Batas bawah irama polling (detik) agar salah isi konfigurasi tidak
     * membuat ratusan layar membebani server.
     */
    public const MIN_INTERVAL = 10;

    /**
     * Angka publik untuk election berjalan, lewat cache singkat
     * (Config\Homepage::$liveCacheSeconds). Kunci cache memuat id & status
     * election, sehingga perubahan status tidak pernah terbaca basi.
     *
     * @param array<string, mixed>|null $election ElectionModel::getCurrentElection()
     *
     * @return array<string, mixed> Lihat project()
     */
    public static function build(?array $election, Homepage $config): array
    {
        $interval = self::interval($election['status'] ?? null, $config->livePollSeconds);
        $compute  = static fn (): array => self::project(service('analytics')->snapshot($election), $interval);

        if ($config->liveCacheSeconds <= 0) {
            return $compute();
        }

        $key = sprintf('home_live_%d_%s', (int) ($election['id'] ?? 0), strtolower((string) ($election['status'] ?? 'none')));

        return cache()->remember($key, $config->liveCacheSeconds, $compute);
    }

    /**
     * @param array<string, mixed> $snapshot AnalyticsService::snapshot()
     *
     * @return array{
     *     status: string|null,
     *     label: string,
     *     candidates: list<array{id: int, label: string, percent: float}>,
     *     turnout: array{voted: int, total: int, percent: float},
     *     updated_at: string,
     *     updated_label: string,
     *     poll: array{interval: int}
     * }
     */
    public static function project(array $snapshot, int $interval): array
    {
        $status = $snapshot['election']['status'] ?? null;
        $all    = $snapshot['summary']['all'];
        $time   = Time::parse($snapshot['generated_at']);

        return [
            'status'     => $status,
            'label'      => election_status_label($status),
            'candidates' => array_map(static fn (array $c): array => [
                'id'      => (int) $c['id'],
                'label'   => (string) $c['label'],
                'percent' => (float) $c['percent'],
            ], $snapshot['candidates']),
            'turnout' => [
                'voted'   => (int) $all['voted'],
                'total'   => (int) $all['total'],
                'percent' => (float) $all['participation'],
            ],
            'updated_at'    => $time->format(DATE_ATOM),
            'updated_label' => $time->toLocalizedString('d MMMM yyyy, HH.mm.ss') . ' WIB',
            'poll'          => ['interval' => $interval],
        ];
    }

    /**
     * Irama polling: berjalan saat UPCOMING (menangkap pembukaan/perubahan
     * jadwal) dan ONGOING; berhenti (0) saat FINISHED atau tanpa jadwal.
     */
    public static function interval(?string $status, int $seconds): int
    {
        if ($seconds <= 0 || ! in_array($status, [ElectionModel::STATUS_UPCOMING, ElectionModel::STATUS_ONGOING], true)) {
            return 0;
        }

        return max(self::MIN_INTERVAL, $seconds);
    }
}
