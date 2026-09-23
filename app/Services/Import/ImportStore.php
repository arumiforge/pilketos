<?php

namespace App\Services\Import;

use App\Services\VoterType;
use JsonException;

/**
 * Penyimpanan sementara hasil pratinjau import di writable/uploads/imports.
 *
 * File Excel asli tidak disimpan: yang disimpan hanya baris hasil parse()
 * (JSON), terikat pada admin yang mengunggah dan jenis pemilih, berlaku 1 jam.
 * Token acak 128-bit; admin lain atau jenis pemilih lain tidak dapat memakainya.
 * Folder writable/ berada di luar public/ sehingga tidak dapat diakses via web.
 * Stage 4: commit memakai claim() (rename atomik) sehingga satu pratinjau
 * hanya dapat diimpor sekali walau POST dikirim ulang.
 */
final class ImportStore
{
    public const TTL = 3600;

    private string $dir;

    public function __construct(?string $dir = null)
    {
        $this->dir = rtrim($dir ?? WRITEPATH . 'uploads' . DIRECTORY_SEPARATOR . 'imports', '\\/');
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function save(int $adminId, VoterType $type, array $payload): string
    {
        if (! is_dir($this->dir) && ! mkdir($this->dir, 0775, true) && ! is_dir($this->dir)) {
            throw new \RuntimeException('Folder import tidak dapat dibuat: ' . $this->dir);
        }

        $this->purgeExpired();

        $token = bin2hex(random_bytes(16));
        $data  = [
            'token'      => $token,
            'admin_id'   => $adminId,
            'type'       => $type->value,
            'created_at' => time(),
        ] + $payload;

        file_put_contents($this->path($token), json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE), LOCK_EX);

        return $token;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function load(string $token, int $adminId, VoterType $type): ?array
    {
        if (! self::validToken($token)) {
            return null;
        }

        $path = $this->path($token);

        if (! is_file($path)) {
            return null;
        }

        try {
            $data = json_decode((string) file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            return null;
        }

        if (! is_array($data)
            || ($data['admin_id'] ?? null) !== $adminId
            || ($data['type'] ?? null) !== $type->value
            || time() - (int) ($data['created_at'] ?? 0) > self::TTL) {
            return null;
        }

        return $data;
    }

    /**
     * Ambil pratinjau untuk diimpor secara ATOMIK (Stage 4): file dipindah ke
     * "<token>.json.claimed" dengan rename(), sehingga request kedua dengan
     * token yang sama (klik ganda, POST diulang, dua tab) tidak mendapat apa-apa
     * dan impor tidak pernah diproses dua kali. Bila impor batal/gagal,
     * release() mengembalikan pratinjau agar admin dapat mencoba lagi.
     *
     * @return array<string, mixed>|null
     */
    public function claim(string $token, int $adminId, VoterType $type): ?array
    {
        $data = $this->load($token, $adminId, $type);

        if ($data === null || ! @rename($this->path($token), $this->claimedPath($token))) {
            return null;
        }

        return $data;
    }

    public function release(string $token): void
    {
        if (self::validToken($token) && is_file($this->claimedPath($token))) {
            @rename($this->claimedPath($token), $this->path($token));
        }
    }

    public function delete(string $token): void
    {
        if (self::validToken($token)) {
            @unlink($this->path($token));
            @unlink($this->claimedPath($token));
        }
    }

    public static function validToken(string $token): bool
    {
        return preg_match('/^[a-f0-9]{32}$/', $token) === 1;
    }

    private function path(string $token): string
    {
        return $this->dir . DIRECTORY_SEPARATOR . $token . '.json';
    }

    private function claimedPath(string $token): string
    {
        return $this->path($token) . '.claimed';
    }

    private function purgeExpired(): void
    {
        $files = array_merge(
            glob($this->dir . DIRECTORY_SEPARATOR . '*.json') ?: [],
            glob($this->dir . DIRECTORY_SEPARATOR . '*.json.claimed') ?: [],
        );

        foreach ($files as $file) {
            if (time() - (int) @filemtime($file) > self::TTL) {
                @unlink($file);
            }
        }
    }
}
