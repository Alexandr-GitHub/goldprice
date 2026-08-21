<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Money;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testRoundMoneyToWholeRubles(): void
    {
        $this->assertSame('1235.00', Money::roundMoney(1234.555));
        $this->assertSame('0.00', Money::roundMoney(0.004));
        $this->assertSame('100.00', Money::roundMoney(99.5));
        $this->assertSame('99.00', Money::roundMoney(99.49));
    }

    public function testToKopecksConvertsRubles(): void
    {
        $this->assertSame(123456, Money::toKopecks(1234.56));
        $this->assertSame(1, Money::toKopecks(0.005));
        $this->assertSame(0, Money::toKopecks(0.004));
    }

    public function testCompareKopecks(): void
    {
        $this->assertSame(-1, Money::compareKopecks(100, 200));
        $this->assertSame(0, Money::compareKopecks(150, 150));
        $this->assertSame(1, Money::compareKopecks(300, 200));
    }
}
