<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storm;

use GoldPrice\Domain\Quote\Quote;

/**
 * Storm mode detector (ТЗ п.6.2). No $modx, no DB, no clock of its own.
 *
 * The market move is measured in ₽/g, because prices are in roubles: a USD/RUB
 * jump moves our cost basis exactly like an XAU/USD jump does.
 *
 * Cold start is deliberate: with no quote old enough to cover the window the
 * market is treated as calm. One data point is not a trend.
 */
final class StormDetector
{
    /** A baseline older than window × this is a history gap, not evidence about the window. */
    private const BASELINE_MAX_AGE_FACTOR = 2;

    /**
     * @param array<int,array<string,mixed>> $history goldprice_quote rows: created_at, xau_usd, usd_rub
     * @param array<string,mixed>|null $state goldprice_state row of the group
     */
    public static function fromHistory(
        array $history,
        float $stoplossPct,
        ?array $state,
        int $windowSeconds,
        int $durationSeconds,
        int $now
    ): StormDecision {
        return self::decide(
            self::changePct($history, $windowSeconds, $now),
            $stoplossPct,
            $state,
            $durationSeconds,
            $now
        );
    }

    /**
     * @param float|null $changePct null when the window cannot be measured
     * @param array<string,mixed>|null $state goldprice_state row of the group
     */
    public static function decide(
        ?float $changePct,
        float $stoplossPct,
        ?array $state,
        int $durationSeconds,
        int $now
    ): StormDecision {
        if ($durationSeconds <= 0) {
            throw new \InvalidArgumentException('Storm duration must be positive');
        }

        $previous = self::previousMode($state);
        $expiresAt = self::timestamp($state['expires_at'] ?? null);
        $active = $previous !== StormDecision::MODE_NORMAL && $expiresAt !== null && $expiresAt > $now;

        $trigger = StormDecision::MODE_NORMAL;
        $calmReason = '';
        if ($stoplossPct <= 0) {
            $calmReason = 'Стоп-лосс группы не задан — режим «Шторм» отключён.';
        } elseif ($changePct === null) {
            $calmReason = 'Недостаточно истории котировок для окна наблюдения — режим «Шторм» не активируется.';
        } elseif ($changePct <= -$stoplossPct) {
            $trigger = StormDecision::MODE_CRASH;
        } elseif ($changePct >= $stoplossPct) {
            $trigger = StormDecision::MODE_SPIKE;
        } else {
            $calmReason = sprintf(
                'Рынок спокоен: изменение %s%% в пределах стоп-лосса %s%%.',
                self::pct($changePct),
                self::pct($stoplossPct)
            );
        }

        if ($active) {
            if ($trigger !== StormDecision::MODE_NORMAL && $trigger !== $previous) {
                return StormDecision::storm(
                    $trigger,
                    self::stormReason($trigger, (float) $changePct, $stoplossPct)
                        . ' Смена тренда: прежняя заморозка снята, начат новый цикл.',
                    (float) $changePct,
                    $now,
                    $now + $durationSeconds,
                    StormDecision::EVENT_FLIP
                );
            }

            $held = $changePct ?? (float) ($state['change_pct'] ?? 0);

            return StormDecision::storm(
                $previous,
                self::stormReason($previous, $held, $stoplossPct),
                $held,
                self::timestamp($state['started_at'] ?? null) ?? $now,
                $expiresAt,
                StormDecision::EVENT_NONE
            );
        }

        if ($trigger !== StormDecision::MODE_NORMAL) {
            return StormDecision::storm(
                $trigger,
                self::stormReason($trigger, (float) $changePct, $stoplossPct),
                (float) $changePct,
                $now,
                $now + $durationSeconds,
                $previous === StormDecision::MODE_NORMAL ? StormDecision::EVENT_ON : StormDecision::EVENT_FLIP
            );
        }

        // A stored storm that is no longer active has just been released by the timer.
        return StormDecision::calm(
            $calmReason,
            $changePct,
            $previous === StormDecision::MODE_NORMAL ? StormDecision::EVENT_NONE : StormDecision::EVENT_OFF
        );
    }

    /**
     * Percent change of ₽/g between the newest quote and the newest quote that is
     * at least one window old. Null when the window is not covered by history.
     *
     * @param array<int,array<string,mixed>> $history
     */
    public static function changePct(array $history, int $windowSeconds, int $now): ?float
    {
        if ($windowSeconds <= 0) {
            throw new \InvalidArgumentException('Storm window must be positive');
        }

        $latest = null;
        $baseline = null;
        foreach ($history as $row) {
            $timestamp = self::timestamp($row['created_at'] ?? null);
            $price = self::rubPerGram($row);
            if ($timestamp === null || $timestamp > $now || $price <= 0) {
                continue;
            }
            if ($latest === null || $timestamp > $latest[0]) {
                $latest = [$timestamp, $price];
            }
            if ($timestamp <= $now - $windowSeconds && ($baseline === null || $timestamp > $baseline[0])) {
                $baseline = [$timestamp, $price];
            }
        }

        if ($latest === null || $baseline === null || $latest[0] <= $baseline[0]) {
            return null;
        }
        if (($now - $baseline[0]) > $windowSeconds * self::BASELINE_MAX_AGE_FACTOR) {
            return null;
        }

        return ($latest[1] / $baseline[1] - 1) * 100;
    }

    /**
     * @param array<string,mixed> $row
     */
    private static function rubPerGram(array $row): float
    {
        $xauUsd = (float) ($row['xau_usd'] ?? 0);
        $usdRub = (float) ($row['usd_rub'] ?? 0);

        return ($xauUsd / Quote::OZ_GRAMS) * $usdRub;
    }

    /**
     * @param array<string,mixed>|null $state
     */
    private static function previousMode(?array $state): string
    {
        $mode = (string) ($state['mode'] ?? StormDecision::MODE_NORMAL);

        return $mode === StormDecision::MODE_CRASH || $mode === StormDecision::MODE_SPIKE
            ? $mode
            : StormDecision::MODE_NORMAL;
    }

    private static function stormReason(string $mode, float $changePct, float $stoplossPct): string
    {
        $template = $mode === StormDecision::MODE_CRASH
            ? 'Обвал рынка: %s%% за окно наблюдения при стоп-лоссе %s%%'
                . ' — цена продажи заморожена, выкуп следует за рынком.'
            : 'Резкий рост рынка: %s%% за окно наблюдения при стоп-лоссе %s%%'
                . ' — цена выкупа заморожена, продажа следует за рынком.';

        return sprintf($template, self::pct($changePct), self::pct($stoplossPct));
    }

    /**
     * @param string|int|float|null $value unix seconds or a DB datetime
     */
    private static function timestamp($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }
        if (is_numeric($value)) {
            return (int) $value;
        }
        $timestamp = strtotime((string) $value);

        return $timestamp === false ? null : $timestamp;
    }

    private static function pct(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
