<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

use GoldPrice\Domain\Money;
use GoldPrice\Domain\Product\ProductCatalog;
use GoldPrice\Domain\Quote\Quote;

/**
 * Coin price engine (ТЗ п.4-6, п.10). No $modx, no DB, no rounding except in PriceResult.
 *
 *   cost = (XAU/USD ÷ 31.1) × USD/RUB × weight
 *   sale = cost × (1 + markup%) + fix
 *   buy  = cost × (1 − discount%) − fix
 *
 * VAT applies to the sale side of commemorative coins only; buying from
 * individuals is not a VAT-able supply.
 */
final class PriceCalculator
{
    public static function fromQuote(
        Quote $quote,
        ProductParams $product,
        ?GroupParams $group,
        float $vatPct = 0.0
    ): PriceResult {
        return self::calculate($quote->goldRubPerGramRaw(), $product, $group, $vatPct);
    }

    /**
     * @param float $rubPerGram unrounded metal price per gram
     */
    public static function calculate(
        float $rubPerGram,
        ProductParams $product,
        ?GroupParams $group,
        float $vatPct = 0.0
    ): PriceResult {
        if ($rubPerGram < 0) {
            throw new \InvalidArgumentException('Metal price per gram cannot be negative');
        }
        if ($vatPct < 0) {
            throw new \InvalidArgumentException('VAT percent cannot be negative');
        }

        if ($product->isIgnoreMarket()) {
            return self::fixedPrice($product);
        }

        if ($product->getWeight() <= 0) {
            return PriceResult::skipped('Не указан вес — цена не рассчитана.');
        }
        if ($group === null) {
            return PriceResult::skipped('Не задана весовая группа — цена не рассчитана.');
        }

        $cost = $rubPerGram * $product->getWeight();

        [$sale, $saleCalc] = self::sale($cost, $rubPerGram, $product, $group, $vatPct);
        [$buy, $buyCalc, $buyReason] = self::buy($cost, $sale, $product, $group);

        return PriceResult::priced($cost, $sale, $buy, $saleCalc, $buyCalc, $buyReason);
    }

    /** Old / collectible coins: manual price, market math not involved (ТЗ п.5.3). */
    private static function fixedPrice(ProductParams $product): PriceResult
    {
        if ($product->getFixedPrice() <= 0) {
            return PriceResult::skipped(
                'Включено игнорирование рынка, но фиксированная цена не задана — цена не рассчитана.'
            );
        }

        $sale = $product->getFixedPrice();
        $buy = $product->getBuyoutPrice();
        $saleCalc = 'Игнорирование рынка: фиксированная цена '
            . Money::roundMoney($sale)
            . ' ₽ (себестоимость и проценты не применяются).';

        if ($buy <= 0) {
            $reason = 'Выкуп не предлагается: ручная цена выкупа не задана.';

            return PriceResult::priced(0.0, $sale, null, $saleCalc, $reason, $reason);
        }

        $buyCalc = 'Игнорирование рынка: ручная цена выкупа ' . Money::roundMoney($buy) . ' ₽.';

        return PriceResult::priced(0.0, $sale, $buy, $saleCalc, $buyCalc, '');
    }

    /**
     * @return array{0:float,1:string}
     */
    private static function sale(
        float $cost,
        float $rubPerGram,
        ProductParams $product,
        GroupParams $group,
        float $vatPct
    ): array {
        if ($product->isUseCustom()) {
            $pct = $product->getCustomPct();
            $fix = $product->getCustomFix();
            $source = 'наценка товара';
        } else {
            $pct = $group->getSaleMarkupPct();
            $fix = $group->getSaleFix();
            $source = 'наценка группы';
        }

        $sale = $cost * (1 + $pct / 100) + $fix;

        $calc = sprintf(
            'Себестоимость %s ₽/г × %s г = %s ₽; %s %s%% %s %s ₽ = %s ₽',
            number_format($rubPerGram, 4, '.', ''),
            self::number($product->getWeight()),
            Money::roundMoney($cost),
            $source,
            self::number($pct),
            $fix < 0 ? '−' : '+',
            Money::roundMoney(abs($fix)),
            Money::roundMoney($sale)
        );

        if ($vatPct > 0 && $product->getCoinType() === ProductCatalog::COIN_COMMEMORATIVE) {
            $sale *= 1 + $vatPct / 100;
            $calc .= sprintf(
                '; НДС %s%% (%s) = %s ₽',
                self::number($vatPct),
                ProductCatalog::COIN_COMMEMORATIVE,
                Money::roundMoney($sale)
            );
        }

        return [$sale, $calc . '.'];
    }

    /**
     * @return array{0:float|null,1:string,2:string} buy price is null when buyout is not offered
     */
    private static function buy(float $cost, float $sale, ProductParams $product, GroupParams $group): array
    {
        // custom_buy_* is percent-of-base plus rubles (either may be negative);
        // group buy_discount is a subtractive discount. Both product fields 0 → group.
        $hasCustomBuy = $product->isUseCustom()
            && (abs($product->getCustomBuyPct()) > 0.0 || abs($product->getCustomBuyFix()) > 0.0);
        if ($hasCustomBuy) {
            $pct = $product->getCustomBuyPct();
            $fix = $product->getCustomBuyFix();
            $buy = $cost * (1 + $pct / 100) + $fix;
            $calc = sprintf(
                'Себестоимость %s ₽; надбавка выкупа товара %s%% %s %s ₽ = %s ₽',
                Money::roundMoney($cost),
                self::number($pct),
                $fix < 0 ? '−' : '+',
                Money::roundMoney(abs($fix)),
                Money::roundMoney($buy)
            );
        } else {
            $buy = $cost * (1 - $group->getBuyDiscountPct() / 100) - $group->getBuyFix();
            $calc = sprintf(
                'Себестоимость %s ₽; скидка выкупа группы %s%% %s %s ₽ = %s ₽',
                Money::roundMoney($cost),
                self::number($group->getBuyDiscountPct()),
                $group->getBuyFix() < 0 ? '+' : '−',
                Money::roundMoney(abs($group->getBuyFix())),
                Money::roundMoney($buy)
            );
        }

        // ТЗ п.6.3: keep the spread by lowering the buy side, never by raising the sale side
        $minMargin = $group->getMinMargin();
        if ($minMargin > 0 && ($sale - $buy) < $minMargin) {
            $buy = $sale - $minMargin;
            $calc .= sprintf(
                '; мин. маржа %s ₽ — выкуп понижен до %s ₽',
                Money::roundMoney($minMargin),
                Money::roundMoney($buy)
            );
        }

        // A zero or negative buy price is not an offer, so it must not reach the storefront as one
        if ($buy <= 0) {
            $reason = 'Выкуп не предлагается: расчётная цена выкупа не положительна.';

            return [null, $calc . '; ' . $reason, $reason];
        }

        return [$buy, $calc . '.', ''];
    }

    /** Percent / weight without trailing zeros: 5.0000 → 5, 7.7800 → 7.78 */
    private static function number(float $value): string
    {
        $formatted = number_format($value, 4, '.', '');
        $trimmed = rtrim(rtrim($formatted, '0'), '.');

        return $trimmed === '' || $trimmed === '-' ? '0' : $trimmed;
    }
}
