<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Pricing\PriceBatchPlanner;
use GoldPrice\Domain\Quote\Quote;
use PHPUnit\Framework\TestCase;

final class PriceBatchPlannerTest extends TestCase
{
    public function testBadProductsAreSkippedWithoutStoppingBatch(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [
                ['product_id' => 26, 'weight' => 7.78, 'group_id' => 3],
                ['product_id' => 27, 'weight' => 0, 'group_id' => 3],
                ['product_id' => 28, 'weight' => 7.78, 'group_id' => null],
            ],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000000
        );

        $this->assertTrue($result['ok']);
        $this->assertSame(1, $result['calculated']);
        $this->assertSame(1, $result['updated']);
        $this->assertSame(2, $result['skipped']);
        $this->assertCount(1, $result['prices']);
        $this->assertStringContainsString('вес', implode(' ', array_keys($result['skipped_reasons'])));
        $this->assertStringContainsString('группа', implode(' ', array_keys($result['skipped_reasons'])));
    }

    public function testProductThatStoppedBeingComputableReportsItsStoredPrice(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [
                ['product_id' => 26, 'weight' => 0, 'group_id' => 3],
                ['product_id' => 27, 'weight' => 0, 'group_id' => 3],
            ],
            [3 => $this->group()],
            [26 => ['product_id' => 26, 'sale_price' => '121909.72', 'buy_price' => '82223.50']],
            0.0,
            900,
            86400,
            1700000000
        );

        // A stored price of a product that no longer computes must not stay on the storefront.
        $this->assertSame(2, $result['skipped']);
        $this->assertSame([26], $result['obsolete']);
    }

    public function testStaleQuoteProducesNoWrites(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000901
        );

        $this->assertTrue($result['ok']);
        $this->assertTrue($result['stale']);
        $this->assertSame([], $result['prices']);
    }

    public function testStaleUsdAlsoProducesNoWrites(): void
    {
        $quote = new Quote(
            2300.0,
            90.0,
            0.0,
            0.0,
            0.0,
            0.0,
            0.0,
            0.0,
            1700000900,
            1700000000,
            ''
        );
        $result = PriceBatchPlanner::plan(
            $quote,
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            899,
            1700000900
        );

        $this->assertTrue($result['ok']);
        $this->assertStringContainsString('не торгуется', $result['message']);
        $this->assertSame([], $result['prices']);
    }

    public function testSecondIdenticalRunIsHeldAndProducesNoWrites(): void
    {
        $first = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000000
        );
        $row = $first['prices'][26];

        $second = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => $row],
            0.0,
            900,
            86400,
            1700000000
        );

        $this->assertSame(1, $second['calculated']);
        $this->assertSame(0, $second['updated']);
        $this->assertSame(1, $second['held']);
        $this->assertSame([], $second['prices']);
    }

    public function testStormFreezesSaleAndLetsBuyFollowTheMarket(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => '200000.00',
                'buy_price' => '150000.00',
                'sale_frozen' => 0,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => true, 'buy' => false, 'reason' => 'Шторм (обвал рынка): продажа приостановлена']]
        );

        $row = $result['prices'][26];
        $this->assertSame('200000.00', $row['sale_price'], 'не продаём дешевле, чем закупили');
        $this->assertSame(1, $row['sale_frozen']);
        $this->assertSame(0, $row['buy_frozen']);
        $this->assertNotSame('150000.00', $row['buy_price'], 'выкуп падает вместе с рынком');
        $this->assertSame(1, $result['frozen_sale']);
        $this->assertSame(0, $result['frozen_buy']);
        $this->assertSame(0, $result['frozen_without_price']);
        $this->assertStringContainsString('Шторм', $row['sale_calc']);
    }

    public function testStormFreezesBuyAndLetsSaleFollowTheMarket(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => '200000.00',
                'buy_price' => '150000.00',
                'sale_frozen' => 0,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => false, 'buy' => true, 'reason' => 'Шторм (резкий рост): приём на выкуп приостановлен']]
        );

        $row = $result['prices'][26];
        $this->assertNotSame('200000.00', $row['sale_price'], 'продажа растёт вместе с рынком');
        $this->assertSame('150000.00', $row['buy_price']);
        $this->assertSame(0, $row['sale_frozen']);
        $this->assertSame(1, $row['buy_frozen']);
        $this->assertSame(1, $result['frozen_buy']);
        $this->assertStringContainsString('Шторм', $row['buy_calc']);
    }

    public function testFrozenSideWithoutStoredPriceGetsCalculatedPriceAndFlag(): void
    {
        $plain = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000000
        );

        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => true, 'buy' => false, 'reason' => 'Шторм (обвал рынка): продажа приостановлена']]
        );

        $row = $result['prices'][26];
        $this->assertSame($plain['prices'][26]['sale_price'], $row['sale_price'], 'нечего замораживать — пишем расчёт');
        $this->assertSame(1, $row['sale_frozen']);
        $this->assertSame(1, $result['frozen_without_price']);
    }

    public function testFreezeFlagIsWrittenEvenWhenPriceDoesNotMove(): void
    {
        $plain = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [],
            0.0,
            900,
            86400,
            1700000000
        );

        $frozen = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => $plain['prices'][26]],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => true, 'buy' => false, 'reason' => 'Шторм (обвал рынка): продажа приостановлена']]
        );

        $this->assertSame(1, $frozen['updated'], 'флаг заморозки должен попасть в базу');
        $this->assertSame(1, $frozen['prices'][26]['sale_frozen']);

        $released = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => $frozen['prices'][26]],
            0.0,
            900,
            86400,
            1700000000
        );

        $this->assertSame(1, $released['updated'], 'снятие заморозки тоже должно записаться');
        $this->assertSame(0, $released['prices'][26]['sale_frozen']);
        $this->assertSame($plain['prices'][26]['sale_calc'], $released['prices'][26]['sale_calc']);
    }

    /**
     * Stored prices arrive from xPDO as floats, and MODX runs under ru_RU where PHP 7.4
     * casts 122352.85 to "122352,85" — MySQL then stores 122352.00.
     */
    public function testFrozenPriceKeepsWholeRublesRegardlessOfLocale(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => 122352.85,
                'buy_price' => 82523.47,
                'sale_frozen' => 0,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => true, 'buy' => true, 'reason' => 'Шторм']]
        );

        $row = $result['prices'][26];
        $this->assertSame('122353.00', $row['sale_price']);
        $this->assertSame('82523.00', $row['buy_price']);
        $this->assertMatchesRegularExpression('/^\d+\.00$/', $row['sale_price']);
        $this->assertMatchesRegularExpression('/^\d+\.00$/', $row['buy_price']);
    }

    public function testStormOfAnotherGroupDoesNotFreezeThisProduct(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [['product_id' => 26, 'weight' => 7.78, 'group_id' => 3]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => '200000.00',
                'buy_price' => '150000.00',
                'sale_frozen' => 0,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000000,
            [7 => ['sale' => true, 'buy' => false, 'reason' => 'Шторм (обвал рынка): продажа приостановлена']]
        );

        $row = $result['prices'][26];
        $this->assertNotSame('200000.00', $row['sale_price']);
        $this->assertSame(0, $row['sale_frozen']);
        $this->assertSame(0, $result['frozen_sale']);
    }

    public function testIgnoreMarketWritesFixedPriceWhenQuoteIsStale(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [[
                'product_id' => 26,
                'weight' => 7.78,
                'group_id' => 3,
                'ignore_market' => 1,
                'fixed_price' => 100,
                'buyout_price' => 524,
            ]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => '82327.65',
                'buy_price' => '70000.00',
                'sale_frozen' => 0,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000901
        );

        $this->assertTrue($result['ok']);
        $this->assertSame('100.00', $result['prices'][26]['sale_price']);
        $this->assertSame('524.00', $result['prices'][26]['buy_price']);
        $this->assertSame(0, $result['prices'][26]['sale_frozen']);
    }

    public function testIgnoreMarketDoesNotStayFrozenUnderStorm(): void
    {
        $result = PriceBatchPlanner::plan(
            $this->quote(1700000000),
            [[
                'product_id' => 26,
                'weight' => 7.78,
                'group_id' => 3,
                'ignore_market' => 1,
                'fixed_price' => 100,
                'buyout_price' => 524,
            ]],
            [3 => $this->group()],
            [26 => [
                'product_id' => 26,
                'sale_price' => '200000.00',
                'buy_price' => '150000.00',
                'sale_frozen' => 1,
                'buy_frozen' => 0,
            ]],
            0.0,
            900,
            86400,
            1700000000,
            [3 => ['sale' => true, 'buy' => false, 'reason' => 'Шторм (обвал рынка): продажа приостановлена']]
        );

        $row = $result['prices'][26];
        $this->assertSame('100.00', $row['sale_price']);
        $this->assertSame('524.00', $row['buy_price']);
        $this->assertSame(0, $row['sale_frozen']);
        $this->assertSame(0, $result['frozen_sale']);
    }

    private function quote(int $timestamp): Quote
    {
        return new Quote(2300.0, 90.0, 0.0, 0.0, 0.0, 0.0, 0.0, 0.0, $timestamp, $timestamp, '');
    }

    private function group(): array
    {
        return [
            'id' => 3,
            'sale_markup' => 12,
            'sale_fix' => 200,
            'buy_discount' => 12,
            'buy_fix' => 300,
            'price_step' => 200,
            'min_margin' => 0,
        ];
    }
}
