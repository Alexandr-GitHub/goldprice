<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Storefront\CartExpiry;
use PHPUnit\Framework\TestCase;

final class CartExpiryTest extends TestCase
{
    public function testStampsMissingAddedAtAndExpiresAfterTtl(): void
    {
        $now = 1_700_000_000;
        $items = [
            'fresh' => ['id' => 26, 'price' => 100, CartExpiry::STAMP => $now - 3599],
            'old' => ['id' => 27, 'price' => 200, CartExpiry::STAMP => $now - 3600],
            'legacy' => ['id' => 28, 'price' => 300],
        ];

        $out = CartExpiry::apply($items, $now, 3600);

        $this->assertTrue($out['changed']);
        $this->assertSame(1, $out['removed']);
        $this->assertArrayHasKey('fresh', $out['items']);
        $this->assertArrayNotHasKey('old', $out['items']);
        $this->assertSame($now, $out['items']['legacy'][CartExpiry::STAMP]);
    }

    public function testZeroTtlLeavesCartAlone(): void
    {
        $items = ['a' => ['id' => 1, CartExpiry::STAMP => 1]];
        $out = CartExpiry::apply($items, 999999, 0);
        $this->assertFalse($out['changed']);
        $this->assertSame($items, $out['items']);
    }

    public function testStampDoesNotResetExistingTime(): void
    {
        $item = CartExpiry::stamp(['id' => 1, CartExpiry::STAMP => 50], 100);
        $this->assertSame(50, $item[CartExpiry::STAMP]);
        $fresh = CartExpiry::stamp(['id' => 1], 100);
        $this->assertSame(100, $fresh[CartExpiry::STAMP]);
    }
}
