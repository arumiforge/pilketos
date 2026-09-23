<?php

namespace App\Services;

use App\Models\AuditLogModel;
use App\Models\ElectionModel;
use CodeIgniter\Database\BaseConnection;
use CodeIgniter\Database\Exceptions\DatabaseException;
use CodeIgniter\I18n\Time;
use Config\Database;
use Throwable;

/**
 * Unlock hak suara oleh admin (MASTER section 18, Stage 3).
 *
 * Unlock TIDAK memilih dan TIDAK mengubah candidate_id: baris suara LOCKED
 * hanya diubah menjadi UNLOCKED (+ unlocked_at) sebagai riwayat, lalu
 * vote_unlock_logs dan audit_logs dicatat, semuanya dalam satu transaction.
 * Setelah itu pemilih harus login dan memilih sendiri (VoteService membuat
 * baris LOCKED baru).
 *
 * Urutan kunci sama dengan VoteService::castVote() agar tidak ada deadlock:
 * 1. election terbaru LOCK IN SHARE MODE;
 * 2. baris pemilih FOR UPDATE (menahan castVote pemilih yang sama);
 * 3. baris suara dibaca setelah kunci pemilih didapat, lalu UPDATE bersyarat
 *    `status = 'LOCKED'` (affectedRows = 1 menjadi penjaga terakhir).
 *
 * Unlock hanya saat election ONGOING: setelah selesai, unlock akan mengurangi
 * hasil akhir tanpa kesempatan memilih ulang.
 */
class UnlockService
{
    public const REASON_MIN = 10;
    public const REASON_MAX = 500;

    private const LOCKED   = 'LOCKED';
    private const UNLOCKED = 'UNLOCKED';

    private const ER_LOCK_WAIT_TIMEOUT = 1205;
    private const ER_LOCK_DEADLOCK     = 1213;

    protected BaseConnection $db;
    protected ElectionModel $elections;

    public function __construct(?BaseConnection $db = null)
    {
        $this->db        = $db ?? Database::connect();
        $this->elections = new ElectionModel($this->db);
    }

    /**
     * Alasan dirapikan (spasi di ujung, baris baru seragam). Null bila panjang tidak valid.
     */
    public static function normalizeReason(mixed $reason): ?string
    {
        if (! is_string($reason)) {
            return null;
        }

        $reason = trim(str_replace(["\r\n", "\r"], "\n", $reason));
        $length = mb_strlen($reason);

        return $length < self::REASON_MIN || $length > self::REASON_MAX ? null : $reason;
    }

    /**
     * @param int $voteId Baris suara yang dilihat admin saat memutuskan unlock
     *                    (mencegah membuka suara lain bila data berubah)
     */
    public function unlock(
        VoterType $type,
        int $voterId,
        int $voteId,
        int $adminId,
        mixed $reason,
        ?Time $now = null,
        ?string $ip = null,
    ): UnlockResult {
        $reason = self::normalizeReason($reason);

        if ($reason === null) {
            return UnlockResult::rejected(UnlockResult::INVALID_REASON);
        }

        $now ??= Time::now();
        $db              = $this->db;
        $previousSetting = (bool) $db->transException;

        $db->transException(true);
        $db->transBegin();

        try {
            $election = $this->lockCurrentElection();
            if ($election === null) {
                return $this->reject(UnlockResult::NO_ELECTION);
            }

            $status = $this->elections->resolveStatus($election, $now);
            if ($status !== ElectionModel::STATUS_ONGOING) {
                return $this->reject(UnlockResult::ELECTION_NOT_ONGOING, $status);
            }

            $voter = $this->lockVoter($type, $voterId);
            if ($voter === null) {
                return $this->reject(UnlockResult::VOTER_NOT_FOUND, $status);
            }

            $vote = $db->table($type->voteTable())
                ->select('id, candidate_id, status, voted_at')
                ->where('id', $voteId)
                ->where($type->voterKey(), $voterId)
                ->where('election_id', (int) $election['id'])
                ->get()
                ->getRowArray();

            if ($vote === null || $vote['status'] !== self::LOCKED) {
                return $this->reject(UnlockResult::VOTE_NOT_ACTIVE, $status);
            }

            $timestamp = $now->toDateTimeString();

            $db->table($type->voteTable())
                ->where('id', $voteId)
                ->where('status', self::LOCKED)
                ->update(['status' => self::UNLOCKED, 'unlocked_at' => $timestamp, 'updated_at' => $timestamp]);

            if ($db->affectedRows() !== 1) {
                return $this->reject(UnlockResult::VOTE_NOT_ACTIVE, $status);
            }

            $db->table('vote_unlock_logs')->insert([
                'election_id'          => (int) $election['id'],
                $type->voterKey()      => $voterId,
                $type->unlockVoteKey() => $voteId,
                'admin_id'             => $adminId,
                'reason'               => $reason,
                'unlocked_at'          => $timestamp,
            ]);
            $logId = (int) $db->insertID();

            $db->table('audit_logs')->insert([
                'admin_id'    => $adminId,
                'action'      => AuditLogModel::UNLOCK_VOTE,
                'description' => sprintf(
                    'Hak suara %s %s (%s %s) dibuka. Baris suara #%d yang dicoblos %s menjadi riwayat UNLOCKED; pemilih harus memilih sendiri.',
                    strtolower($type->label()),
                    $voter['name'],
                    $type->identifierLabel(),
                    $voter['identifier'],
                    $voteId,
                    $vote['voted_at'],
                ),
                'election_id'        => (int) $election['id'],
                'vote_unlock_log_id' => $logId,
                'ip_address'         => $ip ?? AuditLogModel::requestIp(),
                'created_at'         => $timestamp,
            ]);

            $db->transCommit();

            return UnlockResult::ok([
                'log_id'       => $logId,
                'vote_id'      => $voteId,
                'election_id'  => (int) $election['id'],
                'voter'        => $voter,
                'candidate_id' => (int) $vote['candidate_id'],
                'unlocked_at'  => $timestamp,
                'reason'       => $reason,
            ]);
        } catch (DatabaseException $e) {
            $this->rollback();

            if (in_array($e->getCode(), [self::ER_LOCK_DEADLOCK, self::ER_LOCK_WAIT_TIMEOUT], true)) {
                log_message('warning', 'Unlock {type} #{id} perlu diulang: {msg}', [
                    'type' => $type->value,
                    'id'   => $voterId,
                    'msg'  => $e->getMessage(),
                ]);

                return UnlockResult::rejected(UnlockResult::RETRY, ElectionModel::STATUS_ONGOING);
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
     * Election terbaru (definisi sama dengan ElectionModel::getCurrentElection()).
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
     * Kunci baris pemilih (aktif maupun nonaktif) dan ambil identitasnya.
     */
    protected function lockVoter(VoterType $type, int $voterId): ?array
    {
        $sql = $this->db->table($type->voterTable())
            ->select('id, name, ' . $type->identifierColumn() . ' AS identifier, status_aktif', false)
            ->where('id', $voterId)
            ->getCompiledSelect();

        return $this->fetchRow($sql . ' FOR UPDATE');
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

    private function reject(string $status, ?string $electionStatus = null): UnlockResult
    {
        $this->rollback();

        return UnlockResult::rejected($status, $electionStatus);
    }

    private function rollback(): void
    {
        if ($this->db->transDepth > 0) {
            $this->db->transRollback();
        }

        $this->db->resetTransStatus();
    }
}
