<?php

namespace App\Services;

use App\Models\ElectionModel;

/**
 * Hasil VoteService::castVote(). Satu status per kemungkinan hasil, lengkap
 * dengan kode HTTP dan pesan UI agar controller siswa/guru merespons seragam.
 */
final class VoteResult
{
    public const OK                = 'ok';
    public const ALREADY_VOTED     = 'already_voted';
    public const VOTING_CLOSED     = 'voting_closed';
    public const NO_ELECTION       = 'no_election';
    public const INVALID_CANDIDATE = 'invalid_candidate';
    public const VOTER_INACTIVE    = 'voter_inactive';
    public const RETRY             = 'retry';

    /**
     * @param array<string, mixed>|null $vote Baris suara yang baru disimpan (hanya bila OK)
     */
    private function __construct(
        public readonly string $status,
        public readonly ?array $vote = null,
        public readonly ?string $electionStatus = null,
    ) {
    }

    /**
     * @param array<string, mixed> $vote
     */
    public static function ok(array $vote): self
    {
        return new self(self::OK, $vote, ElectionModel::STATUS_ONGOING);
    }

    public static function rejected(string $status, ?string $electionStatus = null): self
    {
        return new self($status, null, $electionStatus);
    }

    public function isOk(): bool
    {
        return $this->status === self::OK;
    }

    public function httpStatus(): int
    {
        return match ($this->status) {
            self::OK                => 200,
            self::ALREADY_VOTED     => 409,
            self::INVALID_CANDIDATE => 422,
            self::RETRY             => 503,
            default                 => 403,
        };
    }

    public function message(): string
    {
        return match ($this->status) {
            self::OK                => 'SUARA BERHASIL DISIMPAN',
            self::ALREADY_VOTED     => 'Hak suara ini sudah digunakan dan terkunci. Pilihan tidak dapat diubah.',
            self::VOTING_CLOSED     => $this->electionStatus === ElectionModel::STATUS_UPCOMING
                ? 'Pemilihan belum dibuka. Pencoblosan hanya dapat dilakukan sesuai jadwal.'
                : 'Pemilihan sudah ditutup. Suara tidak dapat dikirim lagi.',
            self::NO_ELECTION       => 'Jadwal pemilihan belum tersedia.',
            self::INVALID_CANDIDATE => 'Pasangan calon tidak valid. Muat ulang halaman, lalu pilih kembali.',
            self::VOTER_INACTIVE    => 'Akun pemilih ini tidak aktif. Hubungi panitia.',
            self::RETRY             => 'Server sedang sibuk. Suara belum tersimpan, silakan kirim ulang.',
            default                 => 'Suara tidak dapat diproses.',
        };
    }
}
