<?php

namespace App\Services;

use App\Libraries\Grade;
use CodeIgniter\Database\BaseBuilder;
use CodeIgniter\Database\BaseConnection;
use Config\Database;

/**
 * Query daftar, detail, dan pencarian pemilih untuk panel admin (Stage 3).
 *
 * Status memilih selalu dihitung dari suara LOCKED pada election berjalan
 * (definisi sama dengan AnalyticsService), sehingga daftar "belum memilih"
 * dengan status akun "aktif" berjumlah sama dengan angka di dasbor.
 */
final class VoterDirectory
{
    public const PER_PAGE = 25;

    public const VOTE_FILTERS   = ['sudah', 'belum'];
    public const STATUS_FILTERS = ['aktif', 'nonaktif', 'semua'];

    private BaseConnection $db;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db = $db ?? Database::connect();
    }

    /**
     * Filter daftar dari query string; nilai asing diabaikan.
     *
     * @param list<string> $classes
     *
     * @return array{q: string, kelas: string, jk: string, vote: string, status: string}
     */
    public static function filters(VoterType $type, array $input, array $classes): array
    {
        $text   = static fn (mixed $v): string => is_string($v) ? trim($v) : '';
        $kelas  = Grade::normalizeKelas($text($input['kelas'] ?? ''));
        $jk     = strtoupper($text($input['jk'] ?? ''));
        $vote   = $text($input['vote'] ?? '');
        $status = $text($input['status'] ?? '');

        return [
            'q'      => mb_substr($text($input['q'] ?? ''), 0, 100),
            'kelas'  => $type === VoterType::Student && in_array($kelas, $classes, true) ? $kelas : '',
            'jk'     => $type === VoterType::Student && in_array($jk, ['L', 'P'], true) ? $jk : '',
            'vote'   => in_array($vote, self::VOTE_FILTERS, true) ? $vote : '',
            'status' => in_array($status, self::STATUS_FILTERS, true) ? $status : 'aktif',
        ];
    }

    /**
     * @return array{rows: list<array<string, mixed>>, total: int}
     */
    public function paginate(VoterType $type, ?int $electionId, array $filters, int $page, int $perPage = self::PER_PAGE): array
    {
        $total = $this->listQuery($type, $electionId, $filters)->countAllResults();

        $builder = $this->listQuery($type, $electionId, $filters)
            ->select('p.*, v.id AS vote_id, v.voted_at, v.candidate_id, c.nomor_urut', false);

        if ($type === VoterType::Student) {
            $builder->orderBy('p.kelas', 'ASC')
                ->orderBy('p.nomor_absen IS NULL', '', false)
                ->orderBy('p.nomor_absen', 'ASC');
        }

        $rows = $builder->orderBy('p.name', 'ASC')
            ->orderBy('p.id', 'ASC')
            ->limit($perPage, max(0, ($page - 1) * $perPage))
            ->get()
            ->getResultArray();

        return ['rows' => $rows, 'total' => $total];
    }

    public function find(VoterType $type, int $id): ?array
    {
        return $this->db->table($type->voterTable())->where('id', $id)->get()->getRowArray();
    }

    /**
     * Suara LOCKED pemilih pada election berjalan + kandidatnya.
     */
    public function activeVote(VoterType $type, int $voterId, ?int $electionId): ?array
    {
        if ($electionId === null) {
            return null;
        }

        return $this->voteQuery($type)
            ->where('v.' . $type->voterKey(), $voterId)
            ->where('v.election_id', $electionId)
            ->where('v.status', 'LOCKED')
            ->get()
            ->getRowArray();
    }

    /**
     * Seluruh baris suara pemilih (termasuk riwayat UNLOCKED), terbaru dulu.
     *
     * @return list<array<string, mixed>>
     */
    public function history(VoterType $type, int $voterId): array
    {
        return $this->voteQuery($type)
            ->select('e.nama AS election_nama, e.tahun AS election_tahun')
            ->join('elections e', 'e.id = v.election_id')
            ->where('v.' . $type->voterKey(), $voterId)
            ->orderBy('v.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Log unlock untuk pemilih ini + nama admin.
     *
     * @return list<array<string, mixed>>
     */
    public function unlockLogs(VoterType $type, int $voterId): array
    {
        return $this->db->table('vote_unlock_logs l')
            ->select('l.*, a.name AS admin_name, a.username AS admin_username')
            ->join('admins a', 'a.id = l.admin_id')
            ->where('l.' . $type->voterKey(), $voterId)
            ->orderBy('l.id', 'DESC')
            ->get()
            ->getResultArray();
    }

    /**
     * Jumlah jejak suara/unlock. > 0 berarti data pemilih tidak boleh dihapus.
     */
    public function historyCount(VoterType $type, int $voterId): int
    {
        return $this->db->table($type->voteTable())->where($type->voterKey(), $voterId)->countAllResults()
            + $this->db->table('vote_unlock_logs')->where($type->voterKey(), $voterId)->countAllResults();
    }

    /**
     * Pencarian pemilih lintas jenis (halaman unlock): nama atau NISN/NIP.
     * Identitas yang sama persis diletakkan paling atas.
     *
     * @return list<array<string, mixed>>
     */
    public function search(string $query, ?int $electionId, int $limitPerType = 15): array
    {
        $query = trim($query);

        if ($query === '') {
            return [];
        }

        $results = [];

        foreach (VoterType::cases() as $type) {
            $identifier = 'p.' . $type->identifierColumn();
            $kelas      = $type === VoterType::Student ? 'p.kelas' : 'NULL';

            $rows = $this->db->table($type->voterTable() . ' p')
                ->select(
                    "p.id, p.name, {$identifier} AS identifier, {$kelas} AS kelas, p.status_aktif, "
                    . 'v.id AS vote_id, v.voted_at, v.candidate_id, c.nomor_urut, c.nama_ketua, c.nama_wakil',
                    false,
                )
                ->join($type->voteTable() . ' v', $this->lockedJoin($type, $electionId), 'left', false)
                ->join('candidates c', 'c.id = v.candidate_id', 'left')
                ->groupStart()
                ->like('p.name', $query)
                ->orLike($identifier, $query)
                ->groupEnd()
                ->orderBy('p.name', 'ASC')
                ->limit($limitPerType)
                ->get()
                ->getResultArray();

            foreach ($rows as $row) {
                $results[] = $row + ['type' => $type];
            }
        }

        usort($results, static function (array $a, array $b) use ($query): int {
            return [(string) $a['identifier'] !== $query, $a['name']] <=> [(string) $b['identifier'] !== $query, $b['name']];
        });

        return $results;
    }

    private function listQuery(VoterType $type, ?int $electionId, array $f): BaseBuilder
    {
        $builder = $this->db->table($type->voterTable() . ' p')
            ->join($type->voteTable() . ' v', $this->lockedJoin($type, $electionId), 'left', false)
            ->join('candidates c', 'c.id = v.candidate_id', 'left');

        if ($f['q'] !== '') {
            $builder->groupStart()
                ->like('p.name', $f['q'])
                ->orLike('p.' . $type->identifierColumn(), $f['q'])
                ->groupEnd();
        }

        if ($type === VoterType::Student) {
            if ($f['kelas'] !== '') {
                $builder->where('p.kelas', $f['kelas']);
            }

            if ($f['jk'] !== '') {
                $builder->where('p.jenis_kelamin', $f['jk']);
            }
        }

        if ($f['vote'] === 'sudah') {
            $builder->where('v.id IS NOT NULL', null, false);
        } elseif ($f['vote'] === 'belum') {
            $builder->where('v.id IS NULL', null, false);
        }

        if ($f['status'] === 'aktif') {
            $builder->where('p.status_aktif', 1);
        } elseif ($f['status'] === 'nonaktif') {
            $builder->where('p.status_aktif', 0);
        }

        return $builder;
    }

    private function voteQuery(VoterType $type): BaseBuilder
    {
        return $this->db->table($type->voteTable() . ' v')
            ->select('v.*, c.nomor_urut, c.nama_ketua, c.nama_wakil, c.theme_accent')
            ->join('candidates c', 'c.id = v.candidate_id');
    }

    private function lockedJoin(VoterType $type, ?int $electionId): string
    {
        return sprintf(
            'v.%s = p.id AND v.election_id = %d AND v.status = %s',
            $type->voterKey(),
            (int) $electionId,
            $this->db->escape('LOCKED'),
        );
    }
}
