<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Product\WeightGroupMatcher;
use PHPUnit\Framework\TestCase;

final class WeightGroupMatcherTest extends TestCase
{
    /** @var array<int,float> */
    private $groups = [
        1 => 3.11,
        2 => 7.78,
        3 => 15.55,
        4 => 31.1,
    ];

    public function testExactMatch(): void
    {
        $this->assertSame(2, WeightGroupMatcher::match(7.78, $this->groups, 2.0));
        $this->assertSame(4, WeightGroupMatcher::match(31.1, $this->groups, 2.0));
    }

    public function testWithinTolerance(): void
    {
        // 7.78 * 0.02 = 0.1556 → 7.63..7.93
        $this->assertSame(2, WeightGroupMatcher::match(7.7, $this->groups, 2.0));
        $this->assertSame(4, WeightGroupMatcher::match(31.5, $this->groups, 2.0));
    }

    public function testOutsideToleranceReturnsNull(): void
    {
        $this->assertNull(WeightGroupMatcher::match(155.5, $this->groups, 2.0));
        $this->assertNull(WeightGroupMatcher::match(20.0, $this->groups, 2.0));
        $this->assertNull(WeightGroupMatcher::match(0.0, $this->groups, 2.0));
    }

    public function testPicksClosestWhenOverlapping(): void
    {
        // With huge tolerance both 15.55 and 31.1 could match 20 — pick closest.
        $this->assertSame(3, WeightGroupMatcher::match(20.0, $this->groups, 50.0));
    }
}
