<?php

namespace App\Services;

use App\Models\CandidateModel;
use App\Models\ElectionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * Logika voting siswa & guru (Stage 2).
 *
 * Aturan Stage 1 yang dijaga di sini:
 * - voting hanya bila election terbaru berstatus ONGOING menurut waktu server;
 * - identitas pemilih hanya dari sesi (controller), kandidat wajib aktif;
 * - insert suara LOCKED di dalam transaction;
 * - duplicate key uq_*_votes_active = "sudah memilih", bukan error 500;
 * - baris suara tidak pernah diubah/dihapus di sini (unlock = Stage 3).
 *
 * Proteksi race condition (double click, tab ganda, request manual):
 * 1. baris pemilih dikunci `FOR UPDATE` sehingga request paralel milik
 *    pemilih yang sama berjalan bergantian;
 * 2. cek suara aktif dilakukan setelah kunci didapat;
 * 3. unique key database tetap menjadi penjaga terakhir.
 * Baris election & kandidat dibaca `LOCK IN SHARE MODE` (record lock, bukan
 * gap lock): perubahan jadwal/kandidat oleh admin menunggu sampai suara yang
 * sedang diproses selesai, tanpa membuat pemilih saling menunggu.
 * Tabel suara sengaja TIDAK dibaca dengan locking read: gap lock pada index
 * unik bisa membuat dua pemilih berbeda saling deadlock.
 */
class VoteService
{
    /**
     * Nilai ENUM status suara aktif (sama untuk student_votes & teacher_votes).
     */
    private const LOCKED = 'LOCKED';

    private const ER_DUP_ENTRY               = 1062;
    private const ER_DUP_ENTRY_WITH_KEY_NAME = 1586;
    private const ER_LOCK_WAIT_TIMEOUT       = 1205;
    private const ER_LOCK_DEADLOCK           = 1213;

    protected BaseConnection $db;
    protected ElectionModel $elections;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db        = $db ?? Database::connect();
        $this->elections = new ElectionModel($this->db);
    }

    /**
     * Simpan satu suara berstatus LOCKED untuk pemilih pada election aktif.
     *
     * @param array{device?: string, browser?: string} $client Hasil DeviceInfo::fromUserAgent() (dibaca server)
     */
    public function castVote(VoterType $type, int $voterId, int $candidateId, array $client = [], ?Time $now = null): VoteResult
    {
        $now ??= Time::now();
        $db              = $this->db;
        $previousSetting = (bool) $db->transException;

        // Query yang gagal di dalam transaction dilempar sebagai exception
        // (DBDebug) sehingga semua jalur gagal berakhir di rollback yang sama.
        $db->transException(true);
        $db->transBegin();

        try {
            $election = $this->lockCurrentElection();
            if ($election === null) {
                return $this->reject(VoteResult::NO_ELECTION);
            }

            // Status dihitung ulang dari jadwal & waktu server saat submit.
            // syncStatus() (UPDATE) sengaja tidak dipanggil di dalam transaction.
            $status = $this->elections->resolveStatus($election, $now);
            if ($status !== ElectionModel::STATUS_ONGOING) {
                return $this->reject(VoteResult::VOTING_CLOSED, $status);
            }

            if (! $this->lockVoter($type, $voterId)) {
                return $this->reject(VoteResult::VOTER_INACTIVE, $status);
            }

            $candidate = $this->findVotableCandidate($candidateId);
            if ($candidate === null) {
                return $this->reject(VoteResult::INVALID_CANDIDATE, $status);
            }

            if ($this->findActiveVoteId($type, (int) $election['id'], $voterId) !== null) {
                return $this->reject(VoteResult::ALREADY_VOTED, $status);
            }

            $timestamp = $now->toDateTimeString();
            $vote      = [
                'election_id'     => (int) $election['id'],
                $type->voterKey() => $voterId,
                'candidate_id'    => (int) $candidate['id'],
                'status'          => self::LOCKED,
                'voted_at'        => $timestamp,
                'device_info'     => $this->clip($client['device'] ?? null),
                'browser_info'    => $this->clip($client['browser'] ?? null),
                'created_at'      => $timestamp,
                'updated_at'      => $timestamp,
            ];

            if ($db->table($type->voteTable())->insert($vote) === false) {
                // Hanya terjadi bila DBDebug dimatikan (query gagal tanpa exception).
                $error = $db->error();

                throw new DatabaseException((string) $error['message'], (int) $error['code']);
            }

            $vote['id'] = (int) $db->insertID();
            $db->transCommit();

            return VoteResult::ok($vote + ['candidate' => $candidate]);
        } catch (DatabaseException $e) {
            $this->rollback();

            if ($this->isActiveVoteConflict($type, $e)) {
                return VoteResult::rejected(VoteResult::ALREADY_VOTED, ElectionModel::STATUS_ONGOING);
            }

            if (in_array($e->getCode(), [self::ER_LOCK_DEADLOCK, self::ER_LOCK_WAIT_TIMEOUT], true)) {
                log_message('warning', 'Vote {type} #{id} perlu diulang: {msg}', [
                    'type' => $type->value,
                    'id'   => $voterId,
                    'msg'  => $e->getMessage(),
                ]);

                return VoteResult::rejected(VoteResult::RETRY, ElectionModel::STATUS_ONGOING);
            }

            throw $e;
        } catch (Throwable $e) {
            $this->rollback();

            throw $e;
        } finally {
            $db->transException($previousSetting);
        }
    }

    /**
     * Ringkasan hak suara seorang pemilih untuk dasbor & halaman voting.
     * Hanya membaca suara milik pemilih ini (tidak ada data pemilih lain).
     *
     * @return array{election: array|null, status: string|null, vote: array|null, candidate: array|null, canVote: bool}
     */
    public function ballotState(VoterType $type, int $voterId): array
    {
        $election = model(ElectionModel::class)->getCurrentElection();
        $vote     = null;
        $chosen   = null;

        if ($election !== null) {
            $vote = $type->voteModel()->findActiveVote((int) $election['id'], $voterId);

            if ($vote !== null) {
                // Kandidat yang dipilih tetap ditampilkan walau kemudian dinonaktifkan.
                $chosen = model(CandidateModel::class)->find((int) $vote['candidate_id']);
            }
        }

        $status = $election['status'] ?? null;

        return [
            'election'  => $election,
            'status'    => $status,
            'vote'      => $vote,
            'candidate' => $chosen,
            'canVote'   => $status === ElectionModel::STATUS_ONGOING && $vote === null,
        ];
    }

    /**
     * Election terbaru (definisi sama dengan ElectionModel::getCurrentElection()),
     * dibaca dengan shared lock.
     */
    protected function lockCurrentElection(): ?array
    {
        $sql = $this->db->table('elections')
            ->select('id, nama, tahun, start_at, end_at, status')
            ->orderBy('id', 'DESC')
            ->limit(1)
            ->getCompiledSelect();

        return $this->fetchRow($sql . ' LOCK IN SHARE MODE');
    }

    /**
     * Kunci baris pemilih aktif. Request paralel milik pemilih yang sama
     * menunggu di sini sampai transaction pertama selesai.
     */
    protected function lockVoter(VoterType $type, int $voterId): bool
    {
        $sql = $this->db->table($type->voterTable())
            ->select('id')
            ->where('id', $voterId)
            ->where('status_aktif', 1)
            ->getCompiledSelect();

        return $this->fetchRow($sql . ' FOR UPDATE') !== null;
    }

    protected function findVotableCandidate(int $candidateId): ?array
    {
        if ($candidateId < 1) {
            return null;
        }

        $sql = $this->db->table('candidates')
            ->select('id, nomor_urut, nama_ketua, nama_wakil')
            ->where('id', $candidateId)
            ->where('status_aktif', 1)
            ->getCompiledSelect();

        return $this->fetchRow($sql . ' LOCK IN SHARE MODE');
    }

    /**
     * Id suara LOCKED milik pemilih, atau null. Consistent read (tanpa lock),
     * dijalankan setelah baris pemilih terkunci.
     */
    protected function findActiveVoteId(VoterType $type, int $electionId, int $voterId): ?int
    {
        $row = $this->db->table($type->voteTable())
            ->select('id')
            ->where('election_id', $electionId)
            ->where($type->voterKey(), $voterId)
            ->where('status', self::LOCKED)
            ->get()
            ->getRowArray();

        return $row === null ? null : (int) $row['id'];
    }

    private function fetchRow(string $sql): ?array
    {
        $query = $this->db->query($sql);

        if ($query === false) {
            $error = $this->db->error();

            throw new DatabaseException((string) $error['message'], (int) $error['code']);
        }

        return $query->getRowArray();
    }

    private function reject(string $status, ?string $electionStatus = null): VoteResult
    {
        $this->rollback();

        return VoteResult::rejected($status, $electionStatus);
    }

    /**
     * Batalkan transaction bila masih terbuka (CodeIgniter sudah melakukan
     * rollback sendiri sebelum melempar DatabaseException) dan pulihkan
     * transStatus agar transaction berikutnya pada koneksi ini tidak ikut gagal.
     */
    private function rollback(): void
    {
        if ($this->db->transDepth > 0) {
            $this->db->transRollback();
        }

        $this->db->resetTransStatus();
    }

    private function isActiveVoteConflict(VoterType $type, DatabaseException $e): bool
    {
        return in_array($e->getCode(), [self::ER_DUP_ENTRY, self::ER_DUP_ENTRY_WITH_KEY_NAME], true)
            && str_contains($e->getMessage(), $type->activeVoteKey());
    }

    private function clip(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return mb_substr($value, 0, 255);
    }
}
