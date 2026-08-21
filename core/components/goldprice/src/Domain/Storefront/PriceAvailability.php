<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storefront;

/**
 * Freshness and buyout availability for storefront prices (no $modx).
 */
final class PriceAvailability
{
    public const IN_STOCK = 'Есть в наличии';

    /**
     * @param mixed $buyPrice
     */
    public static function isBuyOffered($buyPrice): bool
    {
        if ($buyPrice === null || $buyPrice === '') {
            return false;
        }

        return (float) $buyPrice != 0.0;
    }

    public static function isStale(?string $updatedAt, int $nowTs, int $maxAgeSeconds): bool
    {
        if ($updatedAt === null || $updatedAt === '') {
            return true;
        }

        $ts = strtotime($updatedAt);
        if ($ts === false) {
            return true;
        }

        return ($nowTs - $ts) > $maxAgeSeconds;
    }

    /**
     * Manual (ignore-market) prices do not depend on the gold quote heartbeat.
     *
     * @param array<string,mixed>|null $row
     */
    public static function isQuoteStaleForRow(?array $row, ?string $quoteAt, int $nowTs, int $maxAgeSeconds): bool
    {
        if (!empty($row['ignore_market'])) {
            return false;
        }

        return self::isStale($quoteAt, $nowTs, $maxAgeSeconds);
    }

    /**
     * @param mixed $stocks TV `stocks`
     */
    public static function isInStock($stocks): bool
    {
        return (string) $stocks === self::IN_STOCK;
    }
}
