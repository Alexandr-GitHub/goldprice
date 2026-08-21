<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Buyout\BuyoutPriceDecision;
use PHPUnit\Framework\TestCase;

final class BuyoutPriceDecisionTest extends TestCase
{
    public function testRejectWithoutPriceRow(): void
    {
        $out = BuyoutPriceDecision::decide(null, '2026-08-20 12:00:00', strtotime('2026-08-20 12:00:00'), 900);
        $this->assertSame('reject', $out['action']);
        $this->assertSame('no_price_row', $out['reason']);
    }

    public function testRejectWhenBuyFrozen(): void
    {
        $out = BuyoutPriceDecision::decide(
            ['buy_price' => 82721.54, 'buy_frozen' => 1],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('reject', $out['action']);
        $this->assertSame('buy_frozen', $out['reason']);
    }

    public function testRejectWhenStaleOrNotOffered(): void
    {
        $stale = BuyoutPriceDecision::decide(
            ['buy_price' => 82721.54, 'buy_frozen' => 0],
            '2026-08-19 12:00:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('stale', $stale['reason']);

        $ignore = BuyoutPriceDecision::decide(
            ['buy_price' => 34.02, 'buy_frozen' => 0, 'ignore_market' => 1],
            '2026-08-19 12:00:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('set', $ignore['action']);
        $this->assertSame('34.00', $ignore['price']);

        $zero = BuyoutPriceDecision::decide(
            ['buy_price' => 0, 'buy_frozen' => 0],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('no_buy_price', $zero['reason']);
    }

    public function testSetUsesServerBuyPrice(): void
    {
        $out = BuyoutPriceDecision::decide(
            ['buy_price' => 82721.54, 'buy_frozen' => 0],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('set', $out['action']);
        $this->assertSame('82722.00', $out['price']);
    }
}
