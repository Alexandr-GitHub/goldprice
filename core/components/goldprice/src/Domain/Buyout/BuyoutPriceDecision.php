<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Buyout;

use GoldPrice\Domain\Money;
use GoldPrice\Domain\Storefront\PriceAvailability;

/**
 * Server-side buyout price: never trust the posted number.
 *
 * reject — no row, stale, frozen, or buy not offered
 * set    — use stored buy_price
 */
final class BuyoutPriceDecision
{
    /**
     * @param array<string,mixed>|null $row
     * @return array{action:string,price?:string,reason?:string}
     */
    public static function decide(?array $row, ?string $quoteAt, int $nowTs, int $maxAgeSeconds): array
    {
        if ($row === null) {
            return ['action' => 'reject', 'reason' => 'no_price_row'];
        }

        if (PriceAvailability::isQuoteStaleForRow($row, $quoteAt, $nowTs, $maxAgeSeconds)) {
            return ['action' => 'reject', 'reason' => 'stale'];
        }

        if (!empty($row['buy_frozen'])) {
            return ['action' => 'reject', 'reason' => 'buy_frozen'];
        }

        if (!PriceAvailability::isBuyOffered($row['buy_price'] ?? 0)) {
            return ['action' => 'reject', 'reason' => 'no_buy_price'];
        }

        return [
            'action' => 'set',
            'price' => Money::roundMoney((float) $row['buy_price']),
        ];
    }
}
