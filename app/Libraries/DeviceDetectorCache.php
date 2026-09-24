<?php

namespace App\Libraries;

use CodeIgniter\Cache\CacheInterface as FrameworkCache;
use DeviceDetector\Cache\CacheInterface;
use Throwable;

/**
 * Jembatan cache matomo/device-detector ke cache CodeIgniter (Stage 11).
 *
 * Tanpa cache, setiap request mengurai ulang berkas YAML regex pustaka itu
 * (70-430 ms per suara). Hasil uraian disimpan di cache aplikasi
 * (bawaan: writable/cache/) tanpa kedaluwarsa: kunci dari pustaka sudah
 * memuat nomor versinya, jadi pembaruan pustaka otomatis memakai kunci baru.
 * Cache yang gagal dibaca/ditulis tidak pernah menggagalkan suara: pustaka
 * cukup mengurai ulang YAML.
 */
final class DeviceDetectorCache implements CacheInterface
{
    private const PREFIX = 'DeviceDetector';

    public function __construct(private readonly FrameworkCache $cache)
    {
    }

    public function fetch(string $id)
    {
        try {
            return $this->cache->get($this->key($id)) ?? false;
        } catch (Throwable) {
            return false;
        }
    }

    public function contains(string $id): bool
    {
        return $this->fetch($id) !== false;
    }

    public function save(string $id, $data, int $lifeTime = 0): bool
    {
        try {
            return (bool) $this->cache->save($this->key($id), $data, max(0, $lifeTime));
        } catch (Throwable) {
            return false;
        }
    }

    public function delete(string $id): bool
    {
        try {
            return (bool) $this->cache->delete($this->key($id));
        } catch (Throwable) {
            return false;
        }
    }

    public function flushAll(): bool
    {
        try {
            return $this->cache->deleteMatching(self::PREFIX . '*') >= 0;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Kunci dari pustaka hanya [a-z0-9_-]; diberi awalan agar mudah dikenali
     * (dan dihapus bersama) di folder cache.
     */
    private function key(string $id): string
    {
        $id = (string) preg_replace('/[^A-Za-z0-9_-]/', '', $id);

        return str_starts_with($id, self::PREFIX) ? $id : self::PREFIX . '-' . $id;
    }
}
