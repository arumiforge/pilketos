<?php

namespace App\Services;

use App\Models\ElectionModel;

/**
 * Hasil akhir pemilihan (Stage 4, MASTER section 17).
 *
 * Hasil akhir HANYA tersedia bila election berstatus FINISHED menurut jadwal
 * & jam server (ElectionModel::resolveStatus(), now >= end_at). Angka diambil
 * dari AnalyticsService::snapshot() sehingga identik dengan dasbor, live count,
 * dan analitik: suara sah = baris LOCKED milik pemilih aktif.
 *
 * Keadaan hasil:
 * - unavailable : pemilihan belum selesai / belum dijadwalkan (tanpa angka);
 * - no_votes    : selesai tetapi tidak ada suara sah;
 * - tie         : perolehan tertinggi sama (panitia memutuskan sesuai aturan);
 * - winner      : tepat satu pasangan dengan suara terbanyak.
 * Perayaan (confetti) hanya untuk keadaan "winner".
 */
final class FinalResult
{
    public const UNAVAILABLE = 'unavailable';
    public const NO_VOTES    = 'no_votes';
    public const TIE         = 'tie';
    public const WINNER      = 'winner';

    /**
     * @param array<string, mixed>|null      $election Election berjalan (status sudah dihitung dari jadwal)
     * @param array<string, mixed>|null      $snapshot AnalyticsService::snapshot(); wajib bila FINISHED
     *
     * @return array{
     *     state: string,
     *     available: bool,
     *     celebrate: bool,
     *     ranking: list<array<string, mixed>>,
     *     winner: array<string, mixed>|null,
     *     tied: list<array<string, mixed>>,
     *     margin: int,
     *     total_votes: int
     * }
     */
    public static function build(?array $election, ?array $snapshot): array
    {
        $empty = [
            'state'       => self::UNAVAILABLE,
            'available'   => false,
            'celebrate'   => false,
            'ranking'     => [],
            'winner'      => null,
            'tied'        => [],
            'margin'      => 0,
            'total_votes' => 0,
        ];

        if (($election['status'] ?? null) !== ElectionModel::STATUS_FINISHED || $snapshot === null) {
            return $empty;
        }

        $ranking = self::rank($snapshot['candidates']);
        $total   = (int) $snapshot['summary']['all']['voted'];
        $top     = $ranking === [] ? 0 : (int) $ranking[0]['votes'];

        $result = ['available' => true, 'ranking' => $ranking, 'total_votes' => $total] + $empty;

        if ($total === 0 || $top === 0) {
            return ['state' => self::NO_VOTES] + $result;
        }

        $leaders = array_values(array_filter($ranking, static fn (array $c): bool => (int) $c['votes'] === $top));

        if (count($leaders) > 1) {
            return ['state' => self::TIE, 'tied' => $leaders] + $result;
        }

        $runnerUp = (int) ($ranking[1]['votes'] ?? 0);

        return [
            'state'     => self::WINNER,
            'celebrate' => true,
            'winner'    => $ranking[0],
            'margin'    => $top - $runnerUp,
        ] + $result;
    }

    /**
     * Urutkan pasangan: suara terbanyak dulu, sama banyak = nomor urut kecil
     * dulu. Peringkat kompetisi standar (1, 2, 2, 4) agar suara sama tidak
     * pernah tampil seolah berbeda peringkat.
     *
     * @param list<array<string, mixed>> $candidates Snapshot::candidates
     *
     * @return list<array<string, mixed>>
     */
    public static function rank(array $candidates): array
    {
        usort($candidates, static fn (array $a, array $b): int => [(int) $b['votes'], (int) $a['number']] <=> [(int) $a['votes'], (int) $b['number']]);

        $ranked   = [];
        $previous = null;
        $rank     = 0;

        foreach (array_values($candidates) as $position => $candidate) {
            if ($previous === null || (int) $candidate['votes'] !== $previous) {
                $rank     = $position + 1;
                $previous = (int) $candidate['votes'];
            }

            $ranked[] = ['rank' => $rank] + $candidate;
        }

        return $ranked;
    }
}
