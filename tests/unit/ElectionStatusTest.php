<?php

use App\Models\ElectionModel;
use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Status election dihitung dari jadwal terhadap waktu server.
 *
 * @internal
 */
final class ElectionStatusTest extends CIUnitTestCase
{
    private array $election = [
        'start_at' => '2026-10-01 07:00:00',
        'end_at'   => '2026-10-01 12:00:00',
    ];

    private function statusAt(string $now): string
    {
        return (new ElectionModel())->resolveStatus($this->election, Time::parse($now));
    }

    public function testBeforeStartIsUpcoming(): void
    {
        $this->assertSame('UPCOMING', $this->statusAt('2026-10-01 06:59:59'));
    }

    public function testAtStartIsOngoing(): void
    {
        $this->assertSame('ONGOING', $this->statusAt('2026-10-01 07:00:00'));
    }

    public function testJustBeforeEndIsOngoing(): void
    {
        $this->assertSame('ONGOING', $this->statusAt('2026-10-01 11:59:59'));
    }

    public function testAtEndIsFinished(): void
    {
        $this->assertSame('FINISHED', $this->statusAt('2026-10-01 12:00:00'));
    }

    public function testAfterEndIsFinished(): void
    {
        $this->assertSame('FINISHED', $this->statusAt('2026-10-02 00:00:00'));
    }
}
