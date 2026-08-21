<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Buyout;

use GoldPrice\Domain\Money;

/**
 * Deal and daily buyout caps. Limit 0 = unlimited.
 */
final class BuyoutLimits
{
    public static function calcAmount(float $price, int $count): string
    {
        return Money::roundMoney($price * $count);
    }

    public static function isDealExceeded(float $amount, float $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        return Money::toKopecks($amount) > Money::toKopecks($limit);
    }

    /**
     * True when doneToday + thisAmount exceeds the daily cap.
     * ponytail: window is created_at of done rows; add approved_at if calendar-day-of-approval is required.
     */
    public static function isDailyExceeded(float $doneToday, float $thisAmount, float $limit): bool
    {
        if ($limit <= 0) {
            return false;
        }

        return (Money::toKopecks($doneToday) + Money::toKopecks($thisAmount)) > Money::toKopecks($limit);
    }
}
