<?php

namespace App\Services;

use App\Models\ElectionModel;

/**
 * Hasil UnlockService::unlock(): status + pesan UI yang seragam untuk
 * halaman unlock, detail pemilih, dan test.
 */
final class UnlockResult
{
    public const OK                   = 'ok';
    public const INVALID_REASON       = 'invalid_reason';
    public const NO_ELECTION          = 'no_election';
    public const ELECTION_NOT_ONGOING = 'election_not_ongoing';
    public const VOTER_NOT_FOUND      = 'voter_not_found';
    public const VOTE_NOT_ACTIVE      = 'vote_not_active';
    public const RETRY                = 'retry';

    /**
     * @param array<string, mixed>|null $unlock Data unlock yang tersimpan (hanya bila OK)
     */
    private function __construct(
        public readonly string $status,
        public readonly ?array $unlock = null,
        public readonly ?string $electionStatus = null,
    ) {
    }

    /**
     * @param array<string, mixed> $unlock
     */
    public static function ok(array $unlock): self
    {
        return new self(self::OK, $unlock, ElectionModel::STATUS_ONGOING);
    }

    public static function rejected(string $status, ?string $electionStatus = null): self
    {
        return new self($status, null, $electionStatus);
    }

    public function isOk(): bool
    {
        return $this->status === self::OK;
    }

    public function message(): string
    {
        return match ($this->status) {
            self::OK                   => 'Hak suara berhasil dibuka. Suara lama disimpan sebagai riwayat dan tidak dihitung; pemilih perlu login lalu memilih sendiri.',
            self::INVALID_REASON       => 'Alasan unlock wajib diisi, 10 sampai 500 karakter.',
            self::NO_ELECTION          => 'Belum ada pemilihan yang dijadwalkan.',
            self::ELECTION_NOT_ONGOING => $this->electionStatus === ElectionModel::STATUS_FINISHED
                ? 'Pemilihan sudah selesai. Unlock tidak diizinkan karena akan mengubah hasil akhir tanpa kesempatan memilih ulang.'
                : 'Pemilihan belum dibuka. Unlock hanya dapat dilakukan saat pemilihan berlangsung.',
            self::VOTER_NOT_FOUND      => 'Pemilih tidak ditemukan.',
            self::VOTE_NOT_ACTIVE      => 'Suara ini sudah tidak aktif (mungkin baru saja dibuka admin lain). Muat ulang data pemilih.',
            self::RETRY                => 'Server sedang sibuk. Unlock belum tersimpan, silakan ulangi.',
            default                    => 'Unlock tidak dapat diproses.',
        };
    }
}
