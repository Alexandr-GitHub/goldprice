<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Storefront\PriceAvailability;
use GoldPrice\Domain\Storefront\StorefrontPricePresenter;
use PHPUnit\Framework\TestCase;

final class StorefrontPriceTest extends TestCase
{
    public function testBuyNotOfferedWhenZeroOrEmpty(): void
    {
        $this->assertFalse(PriceAvailability::isBuyOffered(null));
        $this->assertFalse(PriceAvailability::isBuyOffered(''));
        $this->assertFalse(PriceAvailability::isBuyOffered(0));
        $this->assertFalse(PriceAvailability::isBuyOffered(0.0));
        $this->assertFalse(PriceAvailability::isBuyOffered('0.00'));
        $this->assertTrue(PriceAvailability::isBuyOffered(82000.5));
        $this->assertTrue(PriceAvailability::isBuyOffered('82000.50'));
    }

    public function testStaleWhenMissingOrOldUpdatedAt(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $this->assertTrue(PriceAvailability::isStale(null, $now, 900));
        $this->assertTrue(PriceAvailability::isStale('', $now, 900));
        $this->assertTrue(PriceAvailability::isStale('not-a-date', $now, 900));
        $this->assertTrue(PriceAvailability::isStale('2026-08-20 11:44:59', $now, 900));
        $this->assertFalse(PriceAvailability::isStale('2026-08-20 11:45:01', $now, 900));
        $this->assertTrue(PriceAvailability::isQuoteStaleForRow(
            ['sale_price' => 1],
            '2026-08-19 12:00:00',
            $now,
            900
        ));
        $this->assertFalse(PriceAvailability::isQuoteStaleForRow(
            ['sale_price' => 1, 'ignore_market' => 1],
            '2026-08-19 12:00:00',
            $now,
            900
        ));
        $this->assertTrue(PriceAvailability::isInStock('Есть в наличии'));
        $this->assertFalse(PriceAvailability::isInStock(''));
        $this->assertFalse(PriceAvailability::isInStock('Нет в наличии'));
    }

    public function testPresenterFreshRowWithBuyout(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $row = [
            'sale_price' => 122345.67,
            'buy_price' => 82123.45,
            'sale_frozen' => 0,
            'buy_frozen' => 0,
            'updated_at' => '2026-08-20 11:55:00',
        ];

        $out = StorefrontPricePresenter::present(
            $row,
            80.0,
            $now,
            900,
            'sale paused',
            'buy paused',
            'Цену уточняйте',
            'выкуп не предлагается',
            '2026-08-20 11:55:00'
        );

        $this->assertTrue($out['ok']);
        $this->assertFalse($out['stale']);
        $this->assertSame('122346.00', $out['sale']);
        $this->assertSame('82123.00', $out['buy']);
        $this->assertSame('1529.00', $out['sale_usd']);
        $this->assertSame('1027.00', $out['buy_usd']);
        $this->assertTrue($out['buy_offered']);
        $this->assertSame('', $out['buy_not_offered_msg']);
    }

    public function testPresenterZeroBuyIsNotOffered(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $row = [
            'sale_price' => 1000.0,
            'buy_price' => '0.00',
            'sale_frozen' => 0,
            'buy_frozen' => 0,
            'updated_at' => '2026-08-20 11:59:00',
        ];

        $out = StorefrontPricePresenter::present(
            $row,
            100.0,
            $now,
            900,
            '',
            '',
            'Цену уточняйте',
            'выкуп не предлагается',
            '2026-08-20 11:59:00'
        );

        $this->assertTrue($out['ok']);
        $this->assertFalse($out['buy_offered']);
        $this->assertSame('выкуп не предлагается', $out['buy_not_offered_msg']);
        $this->assertSame('0.00', $out['buy']);
    }

    public function testPresenterStaleQuoteKeepsLastPrice(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $row = [
            'sale_price' => 1000.0,
            'buy_price' => 500.0,
            'sale_frozen' => 0,
            'buy_frozen' => 0,
            'updated_at' => '2026-08-20 11:59:00',
        ];

        $out = StorefrontPricePresenter::present(
            $row,
            100.0,
            $now,
            900,
            '',
            '',
            'Цену уточняйте',
            '',
            '2026-08-19 12:00:00'
        );

        $this->assertTrue($out['ok']);
        $this->assertTrue($out['stale']);
        $this->assertSame('stale', $out['reason']);
        $this->assertSame('1000.00', $out['sale']);
        $this->assertSame('500.00', $out['buy']);
    }

    public function testPresenterShowsDampedPriceWhenQuoteIsFresh(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $row = [
            'sale_price' => 122645.46,
            'buy_price' => 82721.54,
            'sale_frozen' => 0,
            'buy_frozen' => 0,
            'updated_at' => '2026-08-20 10:00:00',
        ];

        $out = StorefrontPricePresenter::present(
            $row,
            83.698,
            $now,
            900,
            '',
            '',
            'Цену уточняйте',
            '',
            '2026-08-20 11:55:00'
        );

        $this->assertTrue($out['ok']);
        $this->assertSame('122645.00', $out['sale']);
    }

    public function testPresenterIgnoreMarketSkipsStaleQuote(): void
    {
        $now = strtotime('2026-08-20 12:00:00');
        $row = [
            'sale_price' => 46.13,
            'buy_price' => 34.02,
            'sale_frozen' => 0,
            'buy_frozen' => 0,
            'ignore_market' => 1,
        ];

        $out = StorefrontPricePresenter::present(
            $row,
            80.0,
            $now,
            900,
            '',
            '',
            'Цену уточняйте',
            '',
            '2026-08-19 12:00:00'
        );

        $this->assertTrue($out['ok']);
        $this->assertFalse($out['stale']);
        $this->assertSame('46.00', $out['sale']);
        $this->assertSame('34.00', $out['buy']);
        $this->assertTrue($out['buy_offered']);
    }

    public function testCatalogChunksHideOunceAndPremium(): void
    {
        $root = dirname(__DIR__, 5);
        foreach (['stock.tpl', 'rustock.tpl'] as $name) {
            $src = file_get_contents($root . '/core/elements/chunks/' . $name);
            $this->assertNotFalse($src);
            $this->assertStringNotContainsString('Цена за 1 унцию', $src, $name);
            $this->assertStringNotContainsString('$gp.sale_oz', $src, $name);
            $this->assertStringNotContainsString('Премия к бирже', $src, $name);
            $this->assertStringContainsString('$stocks == "Есть в наличии"', $src, $name);
            $this->assertStringContainsString('| number: 0', $src, $name);
            $this->assertStringNotContainsString('| number: 2', $src, $name);
            $this->assertStringContainsString('Нет в наличии', $src, $name);
            $this->assertStringContainsString('type="button" disabled', $src, $name);
        }
        foreach (['stockCart.tpl', 'rustockCart.tpl'] as $name) {
            $src = file_get_contents($root . '/core/elements/chunks/' . $name);
            $this->assertNotFalse($src);
            $this->assertStringContainsString('$_modx->resource.stocks == "Есть в наличии"', $src, $name);
            $this->assertStringContainsString('| number: 0', $src, $name);
            $this->assertStringNotContainsString('| number: 2', $src, $name);
            $this->assertStringContainsString('Нет в наличии', $src, $name);
            $this->assertStringContainsString('type="button" class="uk-button uk-button-primary" disabled', $src, $name);
        }
    }
}
