<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Product\RatioMarkup;
use PHPUnit\Framework\TestCase;

final class RatioMarkupTest extends TestCase
{
    public function testSalesRatioBecomesCustomPct(): void
    {
        $r = RatioMarkup::fromSalesPurchase('1.3', '1.1');
        $this->assertTrue($r['use_custom']);
        $this->assertSame(30.0, $r['custom_pct']);

        $r30 = RatioMarkup::fromSalesPurchase('1.15', '1.1');
        $this->assertSame(15.0, $r30['custom_pct']);

        $r34 = RatioMarkup::fromSalesPurchase('1.8', '0.8');
        $this->assertSame(80.0, $r34['custom_pct']);
    }

    /**
     * Legacy purchaseRatio multiplied a manual buyoutPrice, not the spot base,
     * so it must never leak into custom_buy_pct (would buy above market).
     */
    public function testPurchaseRatioIsIgnored(): void
    {
        foreach (['1.1', '0.8', '', null] as $purchase) {
            $r = RatioMarkup::fromSalesPurchase('1.3', $purchase);
            $this->assertSame(0.0, $r['custom_buy_pct']);
        }
    }

    public function testOnesOrEmptyMeanNoCustom(): void
    {
        $a = RatioMarkup::fromSalesPurchase('1', '1');
        $this->assertFalse($a['use_custom']);
        $this->assertSame(0.0, $a['custom_pct']);
        $this->assertSame(0.0, $a['custom_buy_pct']);

        $b = RatioMarkup::fromSalesPurchase('', '');
        $this->assertFalse($b['use_custom']);

        $c = RatioMarkup::fromSalesPurchase(null, null);
        $this->assertFalse($c['use_custom']);
    }

    public function testDefaultSalesRatioMeansNoCustomEvenWithPurchaseRatio(): void
    {
        $r = RatioMarkup::fromSalesPurchase('1', '1.1');
        $this->assertFalse($r['use_custom']);
        $this->assertSame(0.0, $r['custom_pct']);
        $this->assertSame(0.0, $r['custom_buy_pct']);
    }

    public function testRoundsToFourDecimals(): void
    {
        $r = RatioMarkup::fromSalesPurchase('1.3461', '1.1467');
        $this->assertSame(34.61, $r['custom_pct']);
    }
}
