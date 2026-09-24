<?php

use App\Libraries\DeviceDetectorCache;
use App\Libraries\DeviceInfo;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\Mock\MockCache;
use Config\Services;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * Stage 2: device_info / browser_info suara dibaca server dari User-Agent.
 * Stage 11: matomo/device-detector + Client Hints (model HP, versi OS asli).
 *
 * @internal
 */
final class DeviceInfoTest extends CIUnitTestCase
{
    public static function userAgents(): iterable
    {
        yield 'Samsung Internet (model dari User-Agent)' => [
            'Mozilla/5.0 (Linux; Android 13; SM-A145F) AppleWebKit/537.36 (KHTML, like Gecko) SamsungBrowser/25.0 Chrome/121.0.0.0 Mobile Safari/537.36',
            'HP / Android 13 / Samsung Galaxy A14',
            'Samsung Internet 25',
        ];

        // Chrome Android modern membekukan "Android 10; K": bukan versi asli.
        yield 'Chrome Android (User-Agent dibekukan)' => [
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
            'HP / Android',
            'Chrome 140',
        ];

        yield 'Mi Browser' => [
            'Mozilla/5.0 (Linux; U; Android 12; id-id; Redmi Note 10 Build/SKQ1) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/112.0.5615.136 Mobile Safari/537.36 XiaoMi/MiuiBrowser/14.5.20-gn',
            'HP / Android 12 / Xiaomi Redmi Note 10',
            'Mi Browser 14',
        ];

        yield 'vivo Browser (dulu terbaca Chrome)' => [
            'Mozilla/5.0 (Linux; Android 11; V2027 Build/RP1A.200720.012; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/87.0.4280.141 Mobile Safari/537.36 VivoBrowser/9.7.0.2',
            'HP / Android 11 / Vivo Y20i',
            'vivo Browser 9',
        ];

        yield 'Browser dalam aplikasi Instagram' => [
            'Mozilla/5.0 (Linux; Android 14; 23108RN04Y Build/UP1A.231005.007; wv) AppleWebKit/537.36 (KHTML, like Gecko) Version/4.0 Chrome/129.0.6668.100 Mobile Safari/537.36 Instagram 350.0.0.0.0 Android',
            'HP / Android 14 / Xiaomi Redmi 13C',
            'Instagram (dalam aplikasi)',
        ];

        yield 'Opera Mini (dulu terbaca tablet, versi 12)' => [
            'Opera/9.80 (Android; Opera Mini/36.2.2254/119.132; U; id) Presto/2.12.423 Version/12.16',
            'HP / Android',
            'Opera Mini 36',
        ];

        yield 'Firefox Android' => [
            'Mozilla/5.0 (Android 14; Mobile; rv:131.0) Gecko/131.0 Firefox/131.0',
            'HP / Android 14',
            'Firefox 131',
        ];

        yield 'Tablet Android (tanpa token Mobile)' => [
            'Mozilla/5.0 (Linux; Android 13; SM-X200) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/125.0.0.0 Safari/537.36',
            'Tablet / Android 13 / Samsung Galaxy Tab A8 10.5" WiFi',
            'Chrome 125',
        ];

        yield 'iPad Safari' => [
            'Mozilla/5.0 (iPad; CPU OS 17_5 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.5 Mobile/15E148 Safari/604.1',
            'Tablet / iPadOS 17.5 / Apple iPad',
            'Safari 17',
        ];

        yield 'Safari iPhone' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 18_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/18.0 Mobile/15E148 Safari/604.1',
            'HP / iOS 18 / Apple iPhone',
            'Safari 18',
        ];

        yield 'Chrome iPhone (CriOS)' => [
            'Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) CriOS/120.0.6099.119 Mobile/15E148 Safari/604.1',
            'HP / iOS 17 / Apple iPhone',
            'Chrome 120',
        ];

        // Windows NT 10.0 dipakai Windows 10 dan 11: tanpa Client Hints tidak bisa dibedakan.
        yield 'Chrome Windows (lab komputer)' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0.0.0 Safari/537.36',
            'Komputer / Windows 10/11',
            'Chrome 126',
        ];

        yield 'Edge Windows' => [
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36 Edg/140.0.0.0',
            'Komputer / Windows 10/11',
            'Edge 140',
        ];

        yield 'Firefox Windows 7' => [
            'Mozilla/5.0 (Windows NT 6.1; Win64; x64; rv:115.0) Gecko/20100101 Firefox/115.0',
            'Komputer / Windows 7',
            'Firefox 115',
        ];

        yield 'Chromebook (dulu terbaca Mac OS X)' => [
            'Mozilla/5.0 (X11; CrOS x86_64 14541.0.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
            'Komputer / ChromeOS',
            'Chrome 140',
        ];

        yield 'Mac (versi 10.15 dibekukan browser)' => [
            'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
            'Komputer / macOS',
            'Chrome 140',
        ];

        yield 'Bot' => [
            'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)',
            'Bot',
            'Googlebot',
        ];

        // Bukan browser: tetap tercatat untuk audit (mis. suara lewat skrip).
        yield 'Skrip curl' => ['curl/8.5.0', DeviceInfo::UNKNOWN, 'curl 8'];

        yield 'Kosong' => ['', DeviceInfo::UNKNOWN, DeviceInfo::UNKNOWN];
    }

    #[DataProvider('userAgents')]
    public function testSummarizesDeviceAndBrowser(string $ua, string $device, string $browser): void
    {
        $this->assertSame(['device' => $device, 'browser' => $browser], DeviceInfo::detect($ua));
    }

    public function testClientHintsRevealWindowsElevenAndPhoneModel(): void
    {
        $windows = DeviceInfo::detect(
            'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36',
            [
                'sec-ch-ua'                  => '"Chromium";v="140", "Not=A?Brand";v="24", "Google Chrome";v="140"',
                'sec-ch-ua-mobile'           => '?0',
                'sec-ch-ua-platform'         => '"Windows"',
                'sec-ch-ua-platform-version' => '"15.0.0"',
            ],
        );
        $this->assertSame(['device' => 'Komputer / Windows 11', 'browser' => 'Chrome 140'], $windows);

        $android = DeviceInfo::detect(
            'Mozilla/5.0 (Linux; Android 10; K) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Mobile Safari/537.36',
            [
                'sec-ch-ua'                  => '"Chromium";v="140", "Google Chrome";v="140"',
                'sec-ch-ua-mobile'           => '?1',
                'sec-ch-ua-platform'         => '"Android"',
                'sec-ch-ua-platform-version' => '"14.0.0"',
                'sec-ch-ua-model'            => '"SM-A546E"',
            ],
        );
        $this->assertSame(['device' => 'HP / Android 14 / Samsung Galaxy A54 5G', 'browser' => 'Chrome 140'], $android);
    }

    public function testReadsUserAgentAndClientHintsFromRequest(): void
    {
        service('superglobals')->setServer('HTTP_USER_AGENT', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/140.0.0.0 Safari/537.36');
        $request = Services::incomingrequest(null, false);
        $request->setHeader('Sec-CH-UA', '"Chromium";v="140", "Brave";v="140", "Not=A?Brand";v="24"');
        $request->setHeader('Sec-CH-UA-Platform', '"Windows"');
        $request->setHeader('Sec-CH-UA-Platform-Version', '"10.0.0"');

        $this->assertSame(['device' => 'Komputer / Windows 10', 'browser' => 'Brave 140'], DeviceInfo::fromRequest($request));
    }

    public function testResultFitsVarchar255(): void
    {
        $info = DeviceInfo::detect('Mozilla/5.0 (Windows NT 10.0) Chrome/' . str_repeat('9', 400));

        $this->assertLessThanOrEqual(255, mb_strlen($info['device']));
        $this->assertLessThanOrEqual(255, mb_strlen($info['browser']));
    }

    public function testCacheBridgeStoresParsedRegexesInFrameworkCache(): void
    {
        $framework = new MockCache();
        $cache     = new DeviceDetectorCache($framework);

        $this->assertFalse($cache->fetch('DeviceDetector-651regexes-os'));
        $this->assertFalse($cache->contains('DeviceDetector-651regexes-os'));

        $this->assertTrue($cache->save('DeviceDetector-651regexes-os', [['regex' => 'Android']]));
        $this->assertSame([['regex' => 'Android']], $cache->fetch('DeviceDetector-651regexes-os'));
        $this->assertSame([['regex' => 'Android']], $framework->get('DeviceDetector-651regexes-os'));

        // Kunci tanpa awalan tetap dikelompokkan di bawah "DeviceDetector".
        $cache->save('bot651-all', 'Googlebot');
        $this->assertSame('Googlebot', $framework->get('DeviceDetector-bot651-all'));

        $this->assertTrue($cache->delete('bot651-all'));
        $this->assertFalse($cache->contains('bot651-all'));
    }
}
