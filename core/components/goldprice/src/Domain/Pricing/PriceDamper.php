<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

/**
 * Atomic sale/buy price-step decision. No MODX or persistence concerns.
 */
final class PriceDamper
{
    public static function shouldUpdate(
        ?string $currentSale,
        ?string $currentBuy,
        string $newSale,
        ?string $newBuy,
        float $priceStep
    ): bool {
        if ($currentSale === null) {
            return true;
        }
        if (($currentBuy === null) !== ($newBuy === null)) {
            return true;
        }

        if (self::cents($currentSale) === self::cents($newSale)
            && ($newBuy === null || self::cents((string) $currentBuy) === self::cents($newBuy))
        ) {
            return false;
        }

        $step = max(0, (int) round($priceStep * 100));
        $saleChanged = abs(self::cents($newSale) - self::cents($currentSale)) >= $step;
        $buyChanged = $newBuy !== null
            && abs(self::cents($newBuy) - self::cents((string) $currentBuy)) >= $step;

        return $saleChanged || $buyChanged;
    }

    private static function cents(string $amount): int
    {
        return (int) round((float) $amount * 100);
    }
}
