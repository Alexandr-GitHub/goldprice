<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Storm\StormDecision;
use GoldPrice\Domain\Storm\StormDetector;
use PHPUnit\Framework\TestCase;

final class StormDetectorTest extends TestCase
{
    private const NOW = 1700000000;
    private const WINDOW = 3600;
    private const DURATION = 3600;

    /** ТЗ п.13 Тест 3: 2300 $ час назад, 2100 $ сейчас, стоп-лосс 5%. */
    public function testCrashFreezesSaleAndLetsBuyFollowTheMarket(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2100.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertTrue($decision->isActive());
        $this->assertTrue($decision->shouldFreezeSale(), 'не продаём дешевле, чем закупили');
        $this->assertFalse($decision->shouldFreezeBuy(), 'выкуп продолжает считаться и падает вместе с рынком');
        $this->assertSame(-8.6957, round((float) $decision->getChangePct(), 4));
        $this->assertSame(self::NOW, $decision->getStartedAt());
        $this->assertSame(self::NOW + self::DURATION, $decision->getExpiresAt());
        $this->assertSame(StormDecision::EVENT_ON, $decision->getEvent());
        $this->assertStringContainsString('Обвал', $decision->getReason());
    }

    public function testSpikeFreezesBuyAndLetsSaleFollowTheMarket(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2100.0], [0, 2300.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_SPIKE, $decision->getMode());
        $this->assertFalse($decision->shouldFreezeSale());
        $this->assertTrue($decision->shouldFreezeBuy());
        $this->assertSame(9.5238, round((float) $decision->getChangePct(), 4));
        $this->assertSame(StormDecision::EVENT_ON, $decision->getEvent());
        $this->assertStringContainsString('рост', $decision->getReason());
    }

    public function testCalmMarketFreezesNothing(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2320.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertFalse($decision->isActive());
        $this->assertFalse($decision->shouldFreezeSale());
        $this->assertFalse($decision->shouldFreezeBuy());
        $this->assertNull($decision->getExpiresAt());
        $this->assertSame(StormDecision::EVENT_NONE, $decision->getEvent());
    }

    public function testExpiredStormIsReleasedAutomatically(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2320.0]]),
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => self::NOW - 7200,
                'expires_at' => self::NOW - 3600,
                'change_pct' => -8.6957,
            ],
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertFalse($decision->shouldFreezeSale());
        $this->assertSame(StormDecision::EVENT_OFF, $decision->getEvent());
    }

    public function testActiveStormHoldsUntilExpiryWithoutRestartingTheTimer(): void
    {
        $startedAt = self::NOW - 600;
        $expiresAt = $startedAt + self::DURATION;
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2100.0]]),
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => $startedAt,
                'expires_at' => $expiresAt,
                'change_pct' => -8.0,
            ],
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertTrue($decision->shouldFreezeSale());
        $this->assertSame($startedAt, $decision->getStartedAt());
        $this->assertSame($expiresAt, $decision->getExpiresAt(), 'повторный триггер не продлевает режим');
        $this->assertSame(StormDecision::EVENT_NONE, $decision->getEvent());
    }

    public function testCalmMarketDoesNotReleaseStormBeforeExpiry(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2310.0]]),
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => self::NOW - 600,
                'expires_at' => self::NOW + 3000,
                'change_pct' => -8.0,
            ],
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertTrue($decision->shouldFreezeSale());
        $this->assertSame(StormDecision::EVENT_NONE, $decision->getEvent());
    }

    public function testTrendFlipUnfreezesAndStartsTheOppositeCycle(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2100.0], [0, 2300.0]]),
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => self::NOW - 600,
                'expires_at' => self::NOW + 3000,
                'change_pct' => -8.6957,
            ],
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_SPIKE, $decision->getMode());
        $this->assertFalse($decision->shouldFreezeSale(), 'продажа разморожена');
        $this->assertTrue($decision->shouldFreezeBuy());
        $this->assertSame(self::NOW, $decision->getStartedAt(), 'начинается новый цикл');
        $this->assertSame(self::NOW + self::DURATION, $decision->getExpiresAt());
        $this->assertSame(StormDecision::EVENT_FLIP, $decision->getEvent());
    }

    public function testColdStartDoesNotActivateStorm(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-600, 2300.0], [0, 2100.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertNull($decision->getChangePct());
        $this->assertStringContainsString('Недостаточно истории', $decision->getReason());
    }

    public function testSingleQuoteIsNotEvidenceOfAnyMarketMove(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[0, 2100.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertNull($decision->getChangePct());
    }

    public function testEmptyHistoryIsNotEvidenceOfAnyMarketMove(): void
    {
        $decision = StormDetector::fromHistory([], 5.0, null, self::WINDOW, self::DURATION, self::NOW);

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertNull($decision->getChangePct());
    }

    /** After downtime the only old quote may be days old — that is not a move inside the window. */
    public function testBaselineOlderThanTwoWindowsIsIgnored(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-259200, 2300.0], [0, 2100.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertNull($decision->getChangePct());
        $this->assertStringContainsString('Недостаточно истории', $decision->getReason());
    }

    public function testGroupThresholdsAreIndependent(): void
    {
        $history = $this->history([[-3600, 2300.0], [0, 2100.0]]);

        $tight = StormDetector::fromHistory($history, 5.0, null, self::WINDOW, self::DURATION, self::NOW);
        $loose = StormDetector::fromHistory($history, 10.0, null, self::WINDOW, self::DURATION, self::NOW);

        $this->assertSame(StormDecision::MODE_CRASH, $tight->getMode());
        $this->assertSame(StormDecision::MODE_NORMAL, $loose->getMode());
        $this->assertSame($tight->getChangePct(), $loose->getChangePct());
    }

    public function testChangeExactlyAtThresholdActivatesStorm(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2000.0], [0, 1900.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertSame(-5.0, round((float) $decision->getChangePct(), 4));
    }

    public function testMissingStoplossDisablesStorm(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[-3600, 2300.0], [0, 2100.0]]),
            0.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertStringContainsString('Стоп-лосс группы не задан', $decision->getReason());
    }

    /** Prices are in roubles, so a currency move is a market move even at a flat XAU/USD. */
    public function testUsdRubMoveAloneCanTriggerStorm(): void
    {
        $decision = StormDetector::fromHistory(
            [
                ['created_at' => self::NOW - 3600, 'xau_usd' => 2300.0, 'usd_rub' => 90.0],
                ['created_at' => self::NOW, 'xau_usd' => 2300.0, 'usd_rub' => 80.0],
            ],
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertSame(-11.1111, round((float) $decision->getChangePct(), 4));
    }

    public function testDatabaseStyleTimestampsAreAccepted(): void
    {
        $decision = StormDetector::fromHistory(
            [
                [
                    'created_at' => date('Y-m-d H:i:s', self::NOW - 3600),
                    'xau_usd' => '2300.0000',
                    'usd_rub' => '90.0000',
                ],
                [
                    'created_at' => date('Y-m-d H:i:s', self::NOW),
                    'xau_usd' => '2100.0000',
                    'usd_rub' => '90.0000',
                ],
            ],
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => date('Y-m-d H:i:s', self::NOW - 600),
                'expires_at' => date('Y-m-d H:i:s', self::NOW + 3000),
                'change_pct' => '-8.6957',
            ],
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertSame(self::NOW + 3000, $decision->getExpiresAt());
    }

    public function testUnsortedHistoryIsHandled(): void
    {
        $decision = StormDetector::fromHistory(
            $this->history([[0, 2100.0], [-1800, 2200.0], [-3600, 2300.0]]),
            5.0,
            null,
            self::WINDOW,
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertSame(-8.6957, round((float) $decision->getChangePct(), 4));
    }

    public function testPrecomputedChangeCanBeUsedDirectly(): void
    {
        $decision = StormDetector::decide(-8.6957, 5.0, null, self::DURATION, self::NOW);

        $this->assertSame(StormDecision::MODE_CRASH, $decision->getMode());
        $this->assertTrue($decision->shouldFreezeSale());
        $this->assertSame(-8.6957, $decision->getChangePct());
    }

    public function testStormWithoutStoredExpiryIsTreatedAsExpired(): void
    {
        $decision = StormDetector::decide(
            -1.0,
            5.0,
            [
                'mode' => StormDecision::MODE_CRASH,
                'started_at' => self::NOW - 600,
                'expires_at' => null,
                'change_pct' => -8.0,
            ],
            self::DURATION,
            self::NOW
        );

        $this->assertSame(StormDecision::MODE_NORMAL, $decision->getMode());
        $this->assertSame(StormDecision::EVENT_OFF, $decision->getEvent());
    }

    /** MODX runs under ru_RU, where PHP 7.4 renders floats with a comma. */
    public function testReasonUsesDotDecimalsUnderRussianLocale(): void
    {
        $previous = setlocale(LC_NUMERIC, '0');
        setlocale(LC_NUMERIC, 'ru_RU.UTF-8', 'ru_RU.utf8', 'ru_RU');
        try {
            $decision = StormDetector::fromHistory(
                $this->history([[-3600, 2300.0], [0, 2100.0]]),
                5.0,
                null,
                self::WINDOW,
                self::DURATION,
                self::NOW
            );

            $this->assertStringContainsString('-8.70%', $decision->getReason());
            $this->assertStringContainsString('5.00%', $decision->getReason());
        } finally {
            setlocale(LC_NUMERIC, $previous === false ? 'C' : $previous);
        }
    }

    /**
     * @param array<int,array{0:int,1:float}> $points offset from now => XAU/USD
     * @return array<int,array<string,mixed>>
     */
    private function history(array $points): array
    {
        $rows = [];
        foreach ($points as [$offset, $xauUsd]) {
            $rows[] = [
                'created_at' => self::NOW + $offset,
                'xau_usd' => $xauUsd,
                'usd_rub' => 90.0,
            ];
        }

        return $rows;
    }
}
