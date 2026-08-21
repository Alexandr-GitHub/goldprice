<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

use GoldPrice\Domain\Money;
use GoldPrice\Domain\Quote\Quote;

/**
 * Pure batch calculation plan. Persistence adapters only load rows and apply writes.
 */
final class PriceBatchPlanner
{
    /**
     * @param array<int,array<string,mixed>> $products
     * @param array<int,array<string,mixed>> $groupsById
     * @param array<int,array<string,mixed>> $currentByProduct
     * @param array<int,array<string,mixed>> $stormByGroup group_id => [sale: bool, buy: bool, reason: string]
     * @return array<string,mixed>
     */
    public static function plan(
        Quote $quote,
        array $products,
        array $groupsById,
        array $currentByProduct,
        float $vatPct,
        int $goldMaxAge,
        int $usdMaxAge,
        int $now,
        array $stormByGroup = []
    ): array {
        $staleParts = [];
        if (!$quote->isGoldFresh($goldMaxAge, $now)) {
            $staleParts[] = 'gold';
        }
        if (!$quote->isUsdFresh($usdMaxAge, $now)) {
            $staleParts[] = 'USDRUB';
        }
        $quoteStale = $staleParts !== [];
        $staleMessage = $quoteStale ? 'Протухшая котировка: ' . implode(', ', $staleParts) : '';

        $summary = self::summary(true, false, 'Пересчёт завершён');
        foreach ($products as $row) {
            try {
                $productId = (int) ($row['product_id'] ?? 0);
                if ($productId <= 0) {
                    throw new \InvalidArgumentException('product_id must be positive');
                }
                $groupId = isset($row['group_id']) ? (int) $row['group_id'] : 0;
                $groupRow = $groupId > 0 && isset($groupsById[$groupId]) ? $groupsById[$groupId] : null;
                $group = $groupRow === null ? null : GroupParams::fromRow($groupRow);
                $product = ProductParams::fromRow($row);
                $ignoreMarket = $product->isIgnoreMarket();
                // Manual prices do not need a live quote. A stale market must not wipe them,
                // and must not freeze them under storm / hold them under the group step.
                if (!$ignoreMarket && $quoteStale) {
                    self::skip($summary, $staleMessage);
                    continue;
                }
                $result = $ignoreMarket
                    ? PriceCalculator::calculate(0.0, $product, $group, $vatPct)
                    : PriceCalculator::fromQuote($quote, $product, $group, $vatPct);

                if (!$result->isComputable()) {
                    self::skip($summary, $result->getReason());
                    if (isset($currentByProduct[$productId])) {
                        $summary['obsolete'][] = $productId;
                    }
                    continue;
                }

                ++$summary['calculated'];
                $current = $currentByProduct[$productId] ?? null;
                // Stored prices come from xPDO as floats: format them, never cast — under
                // ru_RU PHP 7.4 renders 122352.85 as "122352,85" and MySQL stores 122352.00.
                $currentSale = $current === null ? null : Money::roundMoney((float) $current['sale_price']);
                $currentBuy = $current === null || (float) ($current['buy_price'] ?? 0) <= 0
                    ? null
                    : Money::roundMoney((float) $current['buy_price']);
                $step = ($ignoreMarket || $groupRow === null) ? 0.0 : (float) ($groupRow['price_step'] ?? 0);

                $storm = (!$ignoreMarket && $groupId > 0 && isset($stormByGroup[$groupId]))
                    ? $stormByGroup[$groupId]
                    : null;
                $stormReason = $storm === null ? '' : (string) ($storm['reason'] ?? '');
                $newSale = $result->getSalePrice();
                $newBuy = $result->getBuyPrice();
                $saleCalc = $result->getSaleCalc();
                $buyCalc = $result->getBuyCalc();
                $saleFrozen = 0;
                $buyFrozen = 0;

                if ($storm !== null && !empty($storm['sale'])) {
                    $saleFrozen = 1;
                    ++$summary['frozen_sale'];
                    if ($currentSale !== null) {
                        $newSale = $currentSale;
                    } else {
                        // Nothing to hold yet: store the calculated price, but say it is frozen.
                        ++$summary['frozen_without_price'];
                    }
                    $saleCalc = $stormReason . ' Сохранена цена продажи ' . $newSale . ' ₽.';
                }
                if ($storm !== null && !empty($storm['buy']) && ($currentBuy !== null || $newBuy !== null)) {
                    $buyFrozen = 1;
                    ++$summary['frozen_buy'];
                    if ($currentBuy !== null) {
                        $newBuy = $currentBuy;
                    } else {
                        ++$summary['frozen_without_price'];
                    }
                    $buyCalc = $stormReason . ' Сохранена цена выкупа ' . ($newBuy ?? '0.00') . ' ₽.';
                }

                // A freeze flag is part of the stored row, so a flag change is a write even at an identical price.
                $flagsChanged = $current !== null
                    && ((int) ($current['sale_frozen'] ?? 0) !== $saleFrozen
                        || (int) ($current['buy_frozen'] ?? 0) !== $buyFrozen);

                if (!$flagsChanged
                    && !PriceDamper::shouldUpdate($currentSale, $currentBuy, $newSale, $newBuy, $step)
                ) {
                    ++$summary['held'];
                    continue;
                }

                ++$summary['updated'];
                $summary['prices'][$productId] = [
                    'product_id' => $productId,
                    'group_id' => $groupId > 0 ? $groupId : null,
                    'cost' => $result->getCost(),
                    'sale_price' => $newSale,
                    'buy_price' => $newBuy ?? '0.00',
                    'sale_calc' => $saleCalc,
                    'buy_calc' => $buyCalc,
                    'sale_frozen' => $saleFrozen,
                    'buy_frozen' => $buyFrozen,
                ];
            } catch (\Throwable $e) {
                self::skip($summary, 'Ошибка данных товара: ' . $e->getMessage());
            }
        }

        if ($quoteStale && $summary['calculated'] === 0) {
            return self::summary(true, true, 'Рынок не торгуется — цены не обновлялись.');
        }
        $summary['stale'] = $quoteStale;

        return $summary;
    }

    /**
     * @return array<string,mixed>
     */
    private static function summary(bool $ok, bool $stale, string $message): array
    {
        return [
            'ok' => $ok,
            'stale' => $stale,
            'message' => $message,
            'calculated' => 0,
            'updated' => 0,
            'held' => 0,
            'skipped' => 0,
            'skipped_reasons' => [],
            'frozen_sale' => 0,
            'frozen_buy' => 0,
            // frozen side with no stored price yet: the calculated price is stored under the flag
            'frozen_without_price' => 0,
            'prices' => [],
            // stored prices of products that no longer compute — they must be removed, not left on the storefront
            'obsolete' => [],
        ];
    }

    /**
     * @param array<string,mixed> $summary
     */
    private static function skip(array &$summary, string $reason): void
    {
        ++$summary['skipped'];
        $summary['skipped_reasons'][$reason] = ($summary['skipped_reasons'][$reason] ?? 0) + 1;
    }
}
