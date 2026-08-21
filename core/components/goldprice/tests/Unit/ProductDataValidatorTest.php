<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Product\ProductDataValidator;
use PHPUnit\Framework\TestCase;

final class ProductDataValidatorTest extends TestCase
{
    public function testValidPayloadPasses(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '7.78',
            'metal' => 'золото',
            'coin_type' => 'инвестиционные',
            'group_id' => '',
            'use_custom' => '1',
            'custom_pct' => '30',
            'custom_buy_pct' => '10',
            'custom_fix' => '0',
            'custom_buy_fix' => '-300',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '524',
        ], [1, 2, 3, 4]);

        $this->assertTrue($result['ok']);
        $this->assertSame([], $result['errors']);
        $this->assertSame(7.78, $result['data']['weight']);
        $this->assertSame('золото', $result['data']['metal']);
        $this->assertNull($result['data']['group_id']);
        $this->assertSame(524.0, $result['data']['buyout_price']);
        $this->assertSame(30.0, $result['data']['custom_pct']);
        $this->assertSame(10.0, $result['data']['custom_buy_pct']);
        $this->assertSame(-300.0, $result['data']['custom_buy_fix']);
    }

    public function testCustomBuyPctOutOfBoundsRejected(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '1',
            'metal' => '',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '1',
            'custom_pct' => '0',
            'custom_buy_pct' => '-101',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($result['ok']);
        $this->assertTrue(
            (bool) preg_grep('/выкуп|buy|надбавк/iu', $result['errors']),
            'Expected buy-pct error, got: ' . implode('; ', $result['errors'])
        );
    }

    public function testNegativeWeightRejected(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '-1',
            'metal' => 'золото',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '0',
            'custom_pct' => '0',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($result['ok']);
        $this->assertNotEmpty($result['errors']);
    }

    public function testCustomPctOutOfBoundsRejected(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '1',
            'metal' => 'серебро',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '1',
            'custom_pct' => '10000',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($result['ok']);
        $this->assertTrue(
            (bool) preg_grep('/процент|pct|надбавк/iu', $result['errors']),
            'Expected pct-related error, got: ' . implode('; ', $result['errors'])
        );
    }

    public function testUnknownMetalRejected(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '1',
            'metal' => 'метал',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '0',
            'custom_pct' => '0',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertFalse($result['ok']);
    }

    public function testEmptyMetalAllowed(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '0',
            'metal' => '',
            'coin_type' => '',
            'group_id' => '',
            'use_custom' => '0',
            'custom_pct' => '0',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], []);

        $this->assertTrue($result['ok']);
        $this->assertSame('', $result['data']['metal']);
    }

    public function testUnknownGroupIdRejected(): void
    {
        $result = ProductDataValidator::validate([
            'weight' => '7.78',
            'metal' => 'золото',
            'coin_type' => '',
            'group_id' => '99',
            'use_custom' => '0',
            'custom_pct' => '0',
            'custom_fix' => '0',
            'ignore_market' => '0',
            'fixed_price' => '0',
            'buyout_price' => '0',
        ], [1, 2, 3, 4]);

        $this->assertFalse($result['ok']);
    }
}
