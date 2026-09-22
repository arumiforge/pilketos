<?php

namespace App\Libraries;

use CodeIgniter\HTTP\UserAgent;

/**
 * Ringkasan perangkat & browser pemilih dari header User-Agent.
 *
 * Nilai selalu dibaca SERVER dari request (bukan dari input client) lalu
 * disimpan pada device_info / browser_info baris suara untuk tabel detail
 * suara admin (Stage 3). User-Agent bisa dipalsukan, jadi nilai ini hanya
 * informasi audit, bukan dasar keputusan keamanan.
 *
 * Contoh hasil: device "HP / Android", "Tablet / iOS / iPad",
 * "Komputer / Windows 10"; browser "Chrome 126", "Samsung Internet 25".
 */
final class DeviceInfo
{
    public const UNKNOWN = 'Tidak diketahui';

    /**
     * @return array{device: string, browser: string}
     */
    public static function fromUserAgent(UserAgent $agent): array
    {
        $raw = trim($agent->getAgentString());

        if ($raw === '') {
            return ['device' => self::UNKNOWN, 'browser' => self::UNKNOWN];
        }

        $platform = $agent->getPlatform();
        if ($platform === '' || $platform === 'Unknown Platform') {
            $platform = null;
        }

        if ($agent->isRobot()) {
            return [
                'device'  => self::join(['Bot', $platform]),
                'browser' => self::clip($agent->getRobot()),
            ];
        }

        $category = 'Komputer';
        if (self::isTablet($raw)) {
            $category = 'Tablet';
        } elseif ($agent->isMobile() || preg_match('/Mobi/i', $raw) === 1) {
            $category = 'HP';
        }

        $model  = $agent->getMobile();
        $detail = ($model !== '' && ($platform === null || stripos($platform, $model) === false)) ? $model : null;

        return [
            'device'  => self::join([$category, $platform, $detail]),
            'browser' => $agent->isBrowser()
                ? self::clip(trim($agent->getBrowser() . ' ' . self::majorVersion($agent->getVersion())))
                : self::UNKNOWN,
        ];
    }

    private static function isTablet(string $raw): bool
    {
        if (preg_match('/iPad|Tablet|PlayBook|Silk|Kindle/i', $raw) === 1) {
            return true;
        }

        // Tablet Android tidak mengirim token "Mobile".
        return stripos($raw, 'Android') !== false && stripos($raw, 'Mobile') === false;
    }

    private static function majorVersion(string $version): string
    {
        return preg_match('/^\d+/', $version, $match) === 1 ? $match[0] : '';
    }

    /**
     * @param list<string|null> $parts
     */
    private static function join(array $parts): string
    {
        return self::clip(implode(' / ', array_filter($parts, static fn ($part) => $part !== null && $part !== '')));
    }

    private static function clip(string $value): string
    {
        return mb_substr($value, 0, 255);
    }
}
