<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Storefront\CartPriceDecision;
use PHPUnit\Framework\TestCase;

final class CartPriceDecisionTest extends TestCase
{
    public function testSkipWhenProductHasNoGoldpriceRow(): void
    {
        $out = CartPriceDecision::decide(null, '2026-08-20 12:00:00', strtotime('2026-08-20 12:00:00'), 900);
        $this->assertSame('skip', $out['action']);
    }

    public function testRejectWhenSaleIsFrozen(): void
    {
        $out = CartPriceDecision::decide(
            ['sale_price' => 122645.46, 'sale_frozen' => 1],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('reject', $out['action']);
        $this->assertSame('sale_frozen', $out['reason']);
    }

    public function testRejectWhenQuoteIsStale(): void
    {
        $out = CartPriceDecision::decide(
            ['sale_price' => 122645.46, 'sale_frozen' => 0],
            '2026-08-19 12:00:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('reject', $out['action']);
        $this->assertSame('stale', $out['reason']);
    }

    public function testRejectWhenOutOfStockEvenIfPriceIsReady(): void
    {
        $out = CartPriceDecision::decide(
            ['sale_price' => 113.0, 'sale_frozen' => 0, 'ignore_market' => 1],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900,
            false
        );
        $this->assertSame('reject', $out['action']);
        $this->assertSame('out_of_stock', $out['reason']);
    }

    public function testIgnoreMarketAllowsAddWhenQuoteIsStale(): void
    {
        $out = CartPriceDecision::decide(
            ['sale_price' => 46.13, 'sale_frozen' => 0, 'ignore_market' => 1],
            '2026-08-19 12:00:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('set', $out['action']);
        $this->assertSame('46.00', $out['price']);
    }

    public function testSetIgnoresWhateverTheClientPosted(): void
    {
        $out = CartPriceDecision::decide(
            ['sale_price' => 122645.46, 'sale_frozen' => 0],
            '2026-08-20 11:55:00',
            strtotime('2026-08-20 12:00:00'),
            900
        );
        $this->assertSame('set', $out['action']);
        $this->assertSame('122645.00', $out['price']);
    }

    public function testProductIdPrefersEventProductOverPostedId(): void
    {
        $product = new class {
            public function get($field)
            {
                return $field === 'id' ? 45 : null;
            }
        };
        $this->assertSame(45, CartPriceDecision::productId($product, ['id' => 45], 26));
        $this->assertSame(45, CartPriceDecision::productId(null, ['id' => 45], 26));
        $this->assertSame(26, CartPriceDecision::productId(null, [], 26));
        $this->assertSame(0, CartPriceDecision::productId($product = new class {
            public function get($field)
            {
                return 0;
            }
        }, [], 26));
    }
}
