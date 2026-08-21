<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Product\ProductFormPending;
use PHPUnit\Framework\TestCase;

/**
 * Abort validation must not produce a pending row — nothing to write to DB.
 */
final class ProductFormPendingTest extends TestCase
{
    public function testValidPostYieldsPendingPayload(): void
    {
        $gate = ProductFormPending::fromPost([
            'weight' => '7.78',
            'metal' => 'золото',
            'coin_type' => '',
            'group_id' => '2',
            'use_custom' => '1',
            'custom_pct' => '30',
            'custom_buy_pct' => '10',
            'custom_fix' => '0',
            'custom_buy_fix' => '-300',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '524',
        ], [1, 2, 3, 4]);

        $this->assertTrue($gate['ok']);
        $this->assertNotNull($gate['pending']);
        $this->assertSame(30.0, $gate['pending']['custom_pct']);
        $this->assertSame(10.0, $gate['pending']['custom_buy_pct']);
        $this->assertSame(-300.0, $gate['pending']['custom_buy_fix']);
    }

    public function testAbortLeavesNoPendingForDbWrite(): void
    {
        $gate = ProductFormPending::fromPost([
            'weight' => '-1',
            'metal' => 'золото',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '0',
            'custom_pct' => '0',
            'custom_buy_pct' => '0',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($gate['ok']);
        $this->assertNull($gate['pending'], 'Validation abort must not stage a DB write');
        $this->assertNotEmpty($gate['errors']);
    }

    public function testCustomBuyPctOutOfBoundsAbortsWithoutPending(): void
    {
        $gate = ProductFormPending::fromPost([
            'weight' => '1',
            'metal' => '',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '1',
            'custom_pct' => '0',
            'custom_buy_pct' => '10000',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($gate['ok']);
        $this->assertNull($gate['pending']);
    }
}
