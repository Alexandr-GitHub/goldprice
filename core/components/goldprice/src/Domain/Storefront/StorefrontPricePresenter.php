<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storefront;

use GoldPrice\Domain\Money;

/**
 * Maps goldprice_price row + USD rate to a Fenom-friendly payload.
 */
final class StorefrontPricePresenter
{
    /**
     * @param array<string,mixed>|null $row
     * @return array<string,mixed>
     */
    public static function present(
        ?array $row,
        float $usdRate,
        int $nowTs,
        int $maxAgeSeconds,
        string $salePausedMsg,
        string $buyPausedMsg,
        string $unavailableMsg,
        string $buyNotOfferedMsg,
        ?string $quoteAt = null
    ): array {
        if ($row === null) {
            return self::unavailable($unavailableMsg, 'no_price_row');
        }

        $saleRub = (float) ($row['sale_price'] ?? 0);
        if ($saleRub <= 0) {
            return self::unavailable($unavailableMsg, 'no_sale_price');
        }

        // Heartbeat is the latest quote, not price.updated_at. Stale quote still
        // shows the last stored price; templates add chunk gpPriceStale.

        $buyOffered = PriceAvailability::isBuyOffered($row['buy_price'] ?? 0);
        $buyRub = $buyOffered ? (float) $row['buy_price'] : 0.0;

        $sale = Money::roundMoney($saleRub);
        $buy = $buyOffered ? Money::roundMoney($buyRub) : '0.00';
        $saleUsd = $usdRate > 0 ? Money::roundMoney($saleRub / $usdRate) : '0.00';
        $buyUsd = ($buyOffered && $usdRate > 0) ? Money::roundMoney($buyRub / $usdRate) : '0.00';

        $saleFrozen = !empty($row['sale_frozen']);
        $buyFrozen = !empty($row['buy_frozen']);
        $stale = PriceAvailability::isQuoteStaleForRow($row, $quoteAt, $nowTs, $maxAgeSeconds);

        return [
            'ok' => true,
            'stale' => $stale,
            'sale' => $sale,
            'buy' => $buy,
            'sale_usd' => $saleUsd,
            'buy_usd' => $buyUsd,
            'sale_frozen' => $saleFrozen,
            'buy_frozen' => $buyFrozen,
            'buy_offered' => $buyOffered,
            'sale_paused_msg' => $saleFrozen ? $salePausedMsg : '',
            'buy_paused_msg' => $buyFrozen ? $buyPausedMsg : '',
            'unavailable_msg' => '',
            'buy_not_offered_msg' => $buyOffered ? '' : $buyNotOfferedMsg,
            'reason' => $stale ? 'stale' : '',
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private static function unavailable(string $unavailableMsg, string $reason): array
    {
        return [
            'ok' => false,
            'stale' => $reason === 'stale',
            'sale' => '0.00',
            'buy' => '0.00',
            'sale_usd' => '0.00',
            'buy_usd' => '0.00',
            'sale_frozen' => false,
            'buy_frozen' => false,
            'buy_offered' => false,
            'sale_paused_msg' => '',
            'buy_paused_msg' => '',
            'unavailable_msg' => $unavailableMsg,
            'buy_not_offered_msg' => '',
            'reason' => $reason,
        ];
    }
}
