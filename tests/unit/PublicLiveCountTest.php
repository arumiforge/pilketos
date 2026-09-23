<?php

use App\Services\PublicLiveCount;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Redesign beranda: proyeksi live count publik & helper ukuran asset,
 * tanpa database.
 *
 * @internal
 */
final class PublicLiveCountTest extends CIUnitTestCase
{
    public function testPollingIntervalOnlyRunsBeforeAndDuringVoting(): void
    {
        $this->assertSame(30, PublicLiveCount::interval('UPCOMING', 30));
        $this->assertSame(30, PublicLiveCount::interval('ONGOING', 30));
        $this->assertSame(0, PublicLiveCount::interval('FINISHED', 30));
        $this->assertSame(0, PublicLiveCount::interval(null, 30));

        // Konfigurasi terlalu cepat dibatasi; 0 = tanpa pembaruan otomatis.
        $this->assertSame(PublicLiveCount::MIN_INTERVAL, PublicLiveCount::interval('ONGOING', 3));
        $this->assertSame(0, PublicLiveCount::interval('ONGOING', 0));
        $this->assertSame(0, PublicLiveCount::interval('ONGOING', -5));
    }

    public function testProjectionKeepsOnlyPercentagesTurnoutAndTime(): void
    {
        $snapshot = [
            'election'   => ['id' => 1, 'status' => 'ONGOING'],
            'candidates' => [
                ['id' => 7, 'number' => 1, 'label' => '01', 'ketua' => 'A', 'wakil' => 'B', 'votes' => 3, 'percent' => 60.0, 'student_votes' => 2, 'teacher_votes' => 1],
                ['id' => 9, 'number' => 2, 'label' => '02', 'ketua' => 'C', 'wakil' => 'D', 'votes' => 2, 'percent' => 40.0, 'student_votes' => 2, 'teacher_votes' => 0],
            ],
            'summary' => [
                'students' => ['total' => 8, 'voted' => 4],
                'teachers' => ['total' => 2, 'voted' => 1],
                'all'      => ['total' => 10, 'voted' => 5, 'not_voted' => 5, 'participation' => 50.0, 'votes' => [7 => 3, 9 => 2], 'shares' => [7 => 60.0, 9 => 40.0]],
            ],
            'groups'       => ['class' => [['key' => '7A']]],
            'generated_at' => '2026-10-01 10:15:30',
        ];

        $public = PublicLiveCount::project($snapshot, 30);

        $this->assertSame([
            'status'     => 'ONGOING',
            'label'      => 'Sedang Berlangsung',
            'candidates' => [
                ['id' => 7, 'label' => '01', 'percent' => 60.0],
                ['id' => 9, 'label' => '02', 'percent' => 40.0],
            ],
            'turnout'       => ['voted' => 5, 'total' => 10, 'percent' => 50.0],
            'updated_at'    => '2026-10-01T10:15:30+07:00',
            'updated_label' => '1 Oktober 2026, 10.15.30 WIB',
            'poll'          => ['interval' => 30],
        ], $public);
    }

    public function testAssetSizeReadsSvgViewBoxAndRasterImages(): void
    {
        $logo = asset_size('assets/img/brand/logo-light.svg');
        $this->assertIsArray($logo);
        $this->assertGreaterThan($logo[1], $logo[0]); // logo lengkap melebar
        $this->assertSame(asset_size('assets/img/brand/logo-dark.svg'), $logo);

        $this->assertSame([1920, 1080], asset_size('/assets/img/home/intro-desktop.svg'));
        $this->assertSame([1080, 1920], asset_size('assets/img/home/intro-mobile.svg'));

        $icon = asset_size('favicon.ico');
        $this->assertIsArray($icon);
        $this->assertGreaterThan(0, $icon[0]);

        $this->assertNull(asset_size('assets/img/tidak-ada.svg'));
        $this->assertNull(asset_size('assets/css/app.css'));
    }
}
