<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Buyout\BuyoutLimits;
use PHPUnit\Framework\TestCase;

final class BuyoutLimitsTest extends TestCase
{
    public function testDealZeroIsUnlimited(): void
    {
        $this->assertFalse(BuyoutLimits::isDealExceeded(999999.99, 0));
        $this->assertFalse(BuyoutLimits::isDealExceeded(1.0, 0.0));
    }

    public function testDealExceeded(): void
    {
        $this->assertTrue(BuyoutLimits::isDealExceeded(1000.01, 1000));
        $this->assertFalse(BuyoutLimits::isDealExceeded(1000.0, 1000));
        $this->assertFalse(BuyoutLimits::isDealExceeded(999.99, 1000));
    }

    public function testDailyZeroIsUnlimited(): void
    {
        $this->assertFalse(BuyoutLimits::isDailyExceeded(500000, 500000, 0));
    }

    public function testDailyExceeded(): void
    {
        $this->assertTrue(BuyoutLimits::isDailyExceeded(900.0, 200.0, 1000));
        $this->assertFalse(BuyoutLimits::isDailyExceeded(800.0, 200.0, 1000));
        $this->assertFalse(BuyoutLimits::isDailyExceeded(1000.0, 0.0, 1000));
    }

    public function testCalcAmount(): void
    {
        $this->assertSame('165443.00', BuyoutLimits::calcAmount(82721.54, 2));
        $this->assertSame('82722.00', BuyoutLimits::calcAmount(82721.54, 1));
    }
}
