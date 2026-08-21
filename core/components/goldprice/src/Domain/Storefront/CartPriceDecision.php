<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storefront;

use GoldPrice\Domain\Money;

/**
 * Server-side cart price: never trust the posted number.
 *
 * skip  — no goldprice row (accessories keep miniShop2 price)
 * reject — frozen, stale, or missing sale price
 * set   — use the stored sale price
 */
final class CartPriceDecision
{
    /**
     * @param array<string,mixed>|null $row
     * @return array{action:string,price?:string,reason?:string}
     */
    public static function decide(
        ?array $row,
        ?string $quoteAt,
        int $nowTs,
        int $maxAgeSeconds,
        bool $inStock = true
    ): array {
        if ($row === null) {
            return ['action' => 'skip', 'reason' => 'no_price_row'];
        }

        if (!$inStock) {
            return ['action' => 'reject', 'reason' => 'out_of_stock'];
        }

        if (PriceAvailability::isQuoteStaleForRow($row, $quoteAt, $nowTs, $maxAgeSeconds)) {
            return ['action' => 'reject', 'reason' => 'stale'];
        }

        if (!empty($row['sale_frozen'])) {
            return ['action' => 'reject', 'reason' => 'sale_frozen'];
        }

        $sale = (float) ($row['sale_price'] ?? 0);
        if ($sale <= 0) {
            return ['action' => 'reject', 'reason' => 'no_sale_price'];
        }

        return ['action' => 'set', 'price' => Money::roundMoney($sale)];
    }

    /**
     * Resolve the product being priced. Do not fall back to POST if an event
     * product is already present — POST can belong to a different item.
     *
     * @param object|null $product
     * @param array<string,mixed> $data
     */
    public static function productId($product, array $data = [], $postId = 0): int
    {
        if (is_object($product) && method_exists($product, 'get')) {
            $id = (int) $product->get('id');
            if ($id > 0) {
                return $id;
            }
        }
        if (!empty($data['id'])) {
            return (int) $data['id'];
        }
        if ($product !== null) {
            return 0;
        }

        return (int) $postId;
    }
}
