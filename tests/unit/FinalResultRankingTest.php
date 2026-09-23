<?php

use App\Services\FinalResult;
use CodeIgniter\Test\CIUnitTestCase;

/**
 * Stage 4: aturan peringkat & keadaan hasil akhir (tanpa database).
 *
 * @internal
 */
final class FinalResultRankingTest extends CIUnitTestCase
{
    private function candidate(int $id, int $number, int $votes): array
    {
        return ['id' => $id, 'number' => $number, 'label' => sprintf('%02d', $number), 'votes' => $votes, 'accent' => '#15141A'];
    }

    private function snapshot(array $candidates): array
    {
        return [
            'candidates' => $candidates,
            'summary'    => ['all' => ['voted' => array_sum(array_column($candidates, 'votes'))]],
        ];
    }

    public function testRankingSortsByVotesThenNumberWithCompetitionRanks(): void
    {
        $ranked = FinalResult::rank([
            $this->candidate(1, 1, 5),
            $this->candidate(2, 2, 9),
            $this->candidate(3, 3, 5),
            $this->candidate(4, 4, 1),
        ]);

        $this->assertSame([2, 1, 3, 4], array_column($ranked, 'id'));
        $this->assertSame([1, 2, 2, 4], array_column($ranked, 'rank'));
    }

    public function testResultIsUnavailableUnlessElectionIsFinished(): void
    {
        $snapshot = $this->snapshot([$this->candidate(1, 1, 3)]);

        foreach ([null, ['status' => 'UPCOMING'], ['status' => 'ONGOING']] as $election) {
            $result = FinalResult::build($election, $snapshot);
            $this->assertSame(FinalResult::UNAVAILABLE, $result['state']);
            $this->assertFalse($result['available']);
            $this->assertFalse($result['celebrate']);
            $this->assertSame([], $result['ranking']);
        }

        $this->assertFalse(FinalResult::build(['status' => 'FINISHED'], null)['available']);
    }

    public function testSingleWinnerIsCelebratedWithMargin(): void
    {
        $result = FinalResult::build(['status' => 'FINISHED'], $this->snapshot([
            $this->candidate(1, 1, 4),
            $this->candidate(2, 2, 7),
            $this->candidate(3, 3, 2),
        ]));

        $this->assertSame(FinalResult::WINNER, $result['state']);
        $this->assertTrue($result['celebrate']);
        $this->assertSame(2, $result['winner']['id']);
        $this->assertSame(3, $result['margin']);
        $this->assertSame(13, $result['total_votes']);
    }

    public function testTieAtTheTopHasNoWinnerAndNoCelebration(): void
    {
        $result = FinalResult::build(['status' => 'FINISHED'], $this->snapshot([
            $this->candidate(1, 1, 6),
            $this->candidate(2, 2, 6),
            $this->candidate(3, 3, 1),
        ]));

        $this->assertSame(FinalResult::TIE, $result['state']);
        $this->assertNull($result['winner']);
        $this->assertFalse($result['celebrate']);
        $this->assertSame([1, 2], array_column($result['tied'], 'id'));
        $this->assertSame([1, 1, 3], array_column($result['ranking'], 'rank'));
    }

    public function testNoVotesMeansNoWinner(): void
    {
        $result = FinalResult::build(['status' => 'FINISHED'], $this->snapshot([
            $this->candidate(1, 1, 0),
            $this->candidate(2, 2, 0),
        ]));

        $this->assertSame(FinalResult::NO_VOTES, $result['state']);
        $this->assertTrue($result['available']);
        $this->assertFalse($result['celebrate']);
        $this->assertNull($result['winner']);
    }

    public function testSingleCandidateWithVotesWins(): void
    {
        $result = FinalResult::build(['status' => 'FINISHED'], $this->snapshot([$this->candidate(1, 1, 3)]));

        $this->assertSame(FinalResult::WINNER, $result['state']);
        $this->assertSame(3, $result['margin']);
    }
}
