<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * salesRatio → custom_pct.
 *
 * Sign contract: custom_pct is a percent of base, not a discount (1.3 → +30, 0.8 → −20).
 * The legacy salesRatio shares that base — currency chunks priced a coin as
 * spot × weight × salesRatio.
 *
 * purchaseRatio is intentionally NOT converted to custom_buy_pct: it multiplied the
 * manual buyoutPrice TV, not the spot base, so reusing it against spot would mean
 * buying ~10% above market. Buyout therefore falls back to the group discount.
 */
final class RatioMarkup
{
    /**
     * @param mixed $salesRatio
     * @param mixed $purchaseRatio ignored, kept so callers can pass the raw TV
     * @return array{use_custom:bool,custom_pct:float,custom_buy_pct:float}
     */
    public static function fromSalesPurchase($salesRatio, $purchaseRatio)
    {
        $sales = self::parseRatio($salesRatio);

        if ($sales === null || abs($sales - 1.0) < 1e-9) {
            return [
                'use_custom' => false,
                'custom_pct' => 0.0,
                'custom_buy_pct' => 0.0,
            ];
        }

        return [
            'use_custom' => true,
            'custom_pct' => round(($sales - 1.0) * 100, 4),
            'custom_buy_pct' => 0.0,
        ];
    }

    /**
     * @param mixed $raw
     * @return float|null
     */
    private static function parseRatio($raw)
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }
        $s = str_replace(["\xc2\xa0", ' '], '', $s);
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) {
            return null;
        }

        return (float) $s;
    }
}
