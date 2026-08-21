<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Pricing\GroupParams;
use GoldPrice\Domain\Pricing\PriceCalculator;
use GoldPrice\Domain\Pricing\ProductParams;
use GoldPrice\Domain\Quote\Quote;
use PHPUnit\Framework\TestCase;

final class PriceCalculatorTest extends TestCase
{
    /** ТЗ п.5.1 group 1 (1 oz) values. */
    private function ounceGroup(float $minMargin = 0.0): GroupParams
    {
        return GroupParams::fromRow([
            'sale_markup' => 5,
            'sale_fix' => 500,
            'buy_discount' => 5,
            'buy_fix' => 1000,
            'min_margin' => $minMargin,
        ]);
    }

    private function quote(float $xauUsd, float $usdRub): Quote
    {
        return new Quote($xauUsd, $usdRub, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, 1700000000, 1700000000, '');
    }

    /** ТЗ п.13, Тест 1: XAU 2300, USD/RUB 90, 31.1 г, 5%/+500, 5%/−1000. */
    public function testSpecTestOneOunceCoin(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1, 'coin_type' => 'инвестиционные']),
            $this->ounceGroup(),
            22.0
        );

        $this->assertTrue($result->isComputable());
        $this->assertSame('207000.00', $result->getCost());
        $this->assertSame('217850.00', $result->getSalePrice());
        $this->assertSame('195650.00', $result->getBuyPrice());
        $this->assertTrue($result->isBuyOffered());
    }

    public function testCommemorativeCoinGetsVatOnSaleOnly(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1, 'coin_type' => 'памятные']),
            $this->ounceGroup(),
            22.0
        );

        // 217850 × 1.22
        $this->assertSame('265777.00', $result->getSalePrice());
        $this->assertSame('195650.00', $result->getBuyPrice());
        $this->assertStringContainsString('НДС 22%', $result->getSaleCalc());
        $this->assertStringNotContainsString('НДС', $result->getBuyCalc());
    }

    public function testInvestmentCoinHasNoVat(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1, 'coin_type' => 'инвестиционные']),
            $this->ounceGroup(),
            22.0
        );

        $this->assertSame('217850.00', $result->getSalePrice());
        $this->assertStringNotContainsString('НДС', $result->getSaleCalc());
    }

    public function testCustomMarkupReplacesGroupSaleButNotBuy(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow([
                'weight' => 31.1,
                'coin_type' => 'инвестиционные',
                'use_custom' => 1,
                'custom_pct' => 30,
                'custom_fix' => 0,
            ]),
            $this->ounceGroup()
        );

        // 207000 × 1.30, buy still group 5% − 1000
        $this->assertSame('269100.00', $result->getSalePrice());
        $this->assertSame('195650.00', $result->getBuyPrice());
    }

    public function testCustomBuyPctAppliesOnlyWhenNonZero(): void
    {
        $withCustomBuy = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow([
                'weight' => 31.1,
                'use_custom' => 1,
                'custom_pct' => 30,
                'custom_buy_pct' => -20,
            ]),
            $this->ounceGroup()
        );
        // 207000 × 0.80
        $this->assertSame('165600.00', $withCustomBuy->getBuyPrice());

        $zeroCustomBuy = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow([
                'weight' => 31.1,
                'use_custom' => 1,
                'custom_pct' => 30,
                'custom_buy_pct' => 0,
            ]),
            $this->ounceGroup()
        );
        $this->assertSame('195650.00', $zeroCustomBuy->getBuyPrice());
    }

    public function testCustomBuyFixAppliesWhenPctZero(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow([
                'weight' => 31.1,
                'use_custom' => 1,
                'custom_pct' => 30,
                'custom_buy_pct' => 0,
                'custom_buy_fix' => -300,
            ]),
            $this->ounceGroup()
        );
        // 207000 − 300, not the group 5% − 1000
        $this->assertSame('206700.00', $result->getBuyPrice());
    }

    public function testCustomBuyPctAndFixTogether(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow([
                'weight' => 31.1,
                'use_custom' => 1,
                'custom_buy_pct' => -20,
                'custom_buy_fix' => -300,
            ]),
            $this->ounceGroup()
        );
        // 207000 × 0.80 − 300
        $this->assertSame('165300.00', $result->getBuyPrice());
    }

    public function testIgnoreMarketUsesFixedPriceAndOffersNoBuyout(): void
    {
        $result = PriceCalculator::calculate(
            6000.0,
            ProductParams::fromRow([
                'weight' => 0,
                'ignore_market' => 1,
                'fixed_price' => 250000,
                'coin_type' => 'памятные',
            ]),
            null,
            22.0
        );

        $this->assertTrue($result->isComputable());
        $this->assertSame('250000.00', $result->getSalePrice());
        $this->assertSame('0.00', $result->getCost());
        $this->assertFalse($result->isBuyOffered());
        $this->assertNull($result->getBuyPrice());
        $this->assertNotSame('', $result->getBuyCalc());
        $this->assertStringNotContainsString('НДС', $result->getSaleCalc());
    }

    public function testIgnoreMarketUsesManualBuyoutPrice(): void
    {
        $result = PriceCalculator::calculate(
            6000.0,
            ProductParams::fromRow([
                'ignore_market' => 1,
                'fixed_price' => 100,
                'buyout_price' => 524,
            ]),
            null
        );

        $this->assertTrue($result->isComputable());
        $this->assertTrue($result->isBuyOffered());
        $this->assertSame('100.00', $result->getSalePrice());
        $this->assertSame('524.00', $result->getBuyPrice());
    }

    public function testIgnoreMarketWithoutFixedPriceIsNotComputable(): void
    {
        $result = PriceCalculator::calculate(
            6000.0,
            ProductParams::fromRow(['weight' => 31.1, 'ignore_market' => 1, 'fixed_price' => 0]),
            $this->ounceGroup()
        );

        $this->assertFalse($result->isComputable());
        $this->assertStringContainsString('фиксированная цена', $result->getReason());
    }

    public function testMinMarginLowersBuyPrice(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1]),
            $this->ounceGroup(100000.0)
        );

        // sale 217850, gap 22200 < 100000 → buy = 217850 − 100000
        $this->assertSame('217850.00', $result->getSalePrice());
        $this->assertSame('117850.00', $result->getBuyPrice());
        $this->assertStringContainsString('маржа', $result->getBuyCalc());
    }

    public function testBuyoutIsNotOfferedWhenMarginEatsTheWholePrice(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1]),
            $this->ounceGroup(500000.0)
        );

        // A zero buy price would show up on the storefront as a real offer.
        $this->assertTrue($result->isComputable());
        $this->assertSame('217850.00', $result->getSalePrice());
        $this->assertFalse($result->isBuyOffered());
        $this->assertNull($result->getBuyPrice());
        $this->assertStringContainsString('маржа', $result->getBuyCalc());
        $this->assertNotSame('', $result->getBuyReason());
    }

    public function testProductWithoutWeightIsNotComputable(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 0]),
            $this->ounceGroup()
        );

        $this->assertFalse($result->isComputable());
        $this->assertStringContainsString('вес', $result->getReason());
        $this->assertSame('0.00', $result->getSalePrice());
        $this->assertNull($result->getBuyPrice());
    }

    public function testProductWithoutGroupIsNotComputable(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1]),
            null
        );

        $this->assertFalse($result->isComputable());
        $this->assertStringContainsString('группа', $result->getReason());
    }

    public function testNegativeWeightIsProgrammerError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ProductParams::fromRow(['weight' => -1]);
    }

    public function testNegativeBaseIsProgrammerError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PriceCalculator::calculate(-1.0, ProductParams::fromRow(['weight' => 31.1]), $this->ounceGroup());
    }

    public function testNegativeVatIsProgrammerError(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        PriceCalculator::calculate(6000.0, ProductParams::fromRow(['weight' => 31.1]), $this->ounceGroup(), -1.0);
    }

    /**
     * Intermediate ₽/g must stay unrounded: rounding 5787.7814 → 5787.78 first
     * would yield 179999.96 instead of the exact 180000.00.
     */
    public function testIntermediateRubPerGramIsNotRounded(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2000.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1]),
            $this->ounceGroup()
        );

        $this->assertSame('180000.00', $result->getCost());
    }

    public function testCalcBreakdownIsHumanReadable(): void
    {
        $result = PriceCalculator::fromQuote(
            $this->quote(2300.0, 90.0),
            ProductParams::fromRow(['weight' => 31.1, 'coin_type' => 'инвестиционные']),
            $this->ounceGroup()
        );

        $this->assertStringContainsString('31.1', $result->getSaleCalc());
        $this->assertStringContainsString('207000.00', $result->getSaleCalc());
        $this->assertStringContainsString('5%', $result->getSaleCalc());
        $this->assertStringContainsString('217850.00', $result->getSaleCalc());
        $this->assertStringContainsString('195650.00', $result->getBuyCalc());
    }

    public function testGroupAndProductParamsReadDbRowKeys(): void
    {
        $group = GroupParams::fromRow([
            'id' => 3,
            'weight' => '7.7800',
            'title' => '1/4 Унции',
            'sale_markup' => '12.0000',
            'sale_fix' => '200.00',
            'buy_discount' => '12.0000',
            'buy_fix' => '300.00',
            'price_step' => '200.00',
            'stoploss' => '5.0000',
            'min_margin' => '0.00',
        ]);
        $this->assertSame(12.0, $group->getSaleMarkupPct());
        $this->assertSame(200.0, $group->getSaleFix());
        $this->assertSame(12.0, $group->getBuyDiscountPct());
        $this->assertSame(300.0, $group->getBuyFix());
        $this->assertSame(0.0, $group->getMinMargin());

        $product = ProductParams::fromRow([
            'product_id' => 26,
            'weight' => '7.7800',
            'metal' => 'золото',
            'coin_type' => 'инвестиционные',
            'group_id' => 3,
            'use_custom' => '1',
            'custom_pct' => '30.0000',
            'custom_buy_pct' => '0.0000',
            'custom_fix' => '0.00',
            'custom_buy_fix' => '-300.00',
            'ignore_market' => '0',
            'fixed_price' => '0.00',
            'buyout_price' => '480.00',
        ]);
        $this->assertSame(7.78, $product->getWeight());
        $this->assertSame('инвестиционные', $product->getCoinType());
        $this->assertTrue($product->isUseCustom());
        $this->assertSame(30.0, $product->getCustomPct());
        $this->assertSame(0.0, $product->getCustomBuyPct());
        $this->assertSame(-300.0, $product->getCustomBuyFix());
        $this->assertFalse($product->isIgnoreMarket());
        $this->assertSame(480.0, $product->getBuyoutPrice());
    }
}
