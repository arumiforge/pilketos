<?php

use App\Libraries\DeviceInfo;
use CodeIgniter\HTTP\UserAgent;
use CodeIgniter\Test\CIUnitTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Stage 2: device_info / browser_info suara dibaca server dari User-Agent.
 *
 * @internal
 */
final class DeviceInfoTest extends CIUnitTestCase
{
    public static function userAgents(): iterable
    {
        yield 'Samsung Internet (HP Android)' => [
            'Mozilla/5.0 (Linux; Android 13; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36',
            'HP / Android / Samsung',
            'Samsung Internet 25',
        ];

        yield 'Chrome Android' => [
            'Mozilla/5.0 (Linux; Android 12; Redmi Note 11) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.6478.71 Mobile Safari/537.36',
            'HP / Android',
            'Chrome 126',
        ];

        yield 'Mi Browser' => [
            'Mozilla/5.0 (Linux; U; Android 12; id-id; Redmi Note 10 Build/SKQ1) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/112.0.5615.136 Mobile Safari/537.36 XiaoMi/MiuiBrowser/14.5.20-gn',
            'HP / Android',
            'Mi Browser 14',
        ];

        yield 'Tablet Android (tanpa token Mobile)' => [
            'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Tablet / Android',
            'Chrome 125',
        ];

        yield 'iPad Safari' => [
            'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Tablet / iOS / iPad',
            'Safari 17',
        ];

        yield 'Chrome iPhone (CriOS)' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1',
            'HP / iOS / Apple iPhone',
            'Chrome 120',
        ];

        yield 'Chrome Windows (lab komputer)' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Komputer / Windows 10',
            'Chrome 126',
        ];

        yield 'Firefox Windows' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64; rv:128.0) Gecko/20100101 Firefox/128.0',
            'Komputer / Windows 10',
            'Firefox 128',
        ];

        yield 'Bot' => [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Bot',
            'Googlebot',
        ];

        yield 'Tidak dikenal' => ['curl/8.5.0', 'Komputer', DeviceInfo::UNKNOWN];

        yield 'Kosong' => ['', DeviceInfo::UNKNOWN, DeviceInfo::UNKNOWN];
    }

    #[DataProvider('userAgents')]
    public function testSummarizesDeviceAndBrowser(string $ua, string $device, string $browser): void
    {
        service('superglobals')->setServer('HTTP_USER_AGENT', $ua);

        $this->assertSame(['device' => $device, 'browser' => $browser], DeviceInfo::fromUserAgent(new UserAgent()));
    }

    public function testResultFitsVarchar255(): void
    {
        service('superglobals')->setServer('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0) Chrome/' . str_repeat('9', 400));

        $info = DeviceInfo::fromUserAgent(new UserAgent());

        $this->assertLessThanOrEqual(255, mb_strlen($info['device']));
        $this->assertLessThanOrEqual(255, mb_strlen($info['browser']));
    }
}
