<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Pricing\GroupParams;
use PHPUnit\Framework\TestCase;

final class GroupParamsTest extends TestCase
{
    public function testRootWithoutParentUnchanged(): void
    {
        $group = GroupParams::resolve([
            'sale_markup' => 12,
            'sale_fix' => 200,
            'buy_discount' => 12,
            'buy_fix' => 300,
            'min_margin' => 500,
        ], null);

        $this->assertSame(12.0, $group->getSaleMarkupPct());
        $this->assertSame(200.0, $group->getSaleFix());
        $this->assertSame(12.0, $group->getBuyDiscountPct());
        $this->assertSame(300.0, $group->getBuyFix());
        $this->assertSame(500.0, $group->getMinMargin());
        $this->assertSame('', $group->getLabel());
    }

    public function testFromRowDelegatesToResolveWithoutParent(): void
    {
        $group = GroupParams::fromRow(['sale_markup' => 5, 'min_margin' => 0]);
        $this->assertSame(5.0, $group->getSaleMarkupPct());
        $this->assertSame('', $group->getLabel());
    }

    public function testDeltaSummingAndMinMarginFromParent(): void
    {
        $parent = [
            'sale_markup' => 12,
            'sale_fix' => 200,
            'buy_discount' => 12,
            'buy_fix' => 300,
            'min_margin' => 1000,
        ];
        $child = [
            'title' => 'Кенгуру',
            'add_to_parent' => 1,
            'sale_markup' => 2,
            'sale_fix' => 50,
            'buy_discount' => 1,
            'buy_fix' => 20,
            'min_margin' => 999,
        ];

        $group = GroupParams::resolve($child, $parent);

        $this->assertSame(14.0, $group->getSaleMarkupPct());
        $this->assertSame(250.0, $group->getSaleFix());
        $this->assertSame(13.0, $group->getBuyDiscountPct());
        $this->assertSame(320.0, $group->getBuyFix());
        $this->assertSame(1000.0, $group->getMinMargin());
        $this->assertSame('подгруппы «Кенгуру»', $group->getLabel());
    }

    public function testSubgroupOwnMarkupsWhenAddToParentOff(): void
    {
        $parent = [
            'sale_markup' => 12,
            'sale_fix' => 200,
            'buy_discount' => 12,
            'buy_fix' => 300,
            'min_margin' => 1000,
        ];
        $child = [
            'title' => 'Кенгуру',
            'add_to_parent' => 0,
            'sale_markup' => 2,
            'sale_fix' => 50,
            'buy_discount' => 1,
            'buy_fix' => 20,
            'min_margin' => 500,
        ];

        $group = GroupParams::resolve($child, $parent);

        $this->assertSame(2.0, $group->getSaleMarkupPct());
        $this->assertSame(50.0, $group->getSaleFix());
        $this->assertSame(1.0, $group->getBuyDiscountPct());
        $this->assertSame(20.0, $group->getBuyFix());
        $this->assertSame(500.0, $group->getMinMargin());
        $this->assertSame('подгруппы «Кенгуру»', $group->getLabel());
    }

    public function testSubgroupOwnMarkupsWhenAddToParentMissing(): void
    {
        $parent = [
            'sale_markup' => 12,
            'sale_fix' => 200,
            'buy_discount' => 12,
            'buy_fix' => 300,
            'min_margin' => 1000,
        ];
        $child = [
            'title' => 'Кенгуру',
            'sale_markup' => 2,
            'sale_fix' => 50,
            'buy_discount' => 1,
            'buy_fix' => 20,
            'min_margin' => 500,
        ];

        $group = GroupParams::resolve($child, $parent);

        $this->assertSame(2.0, $group->getSaleMarkupPct());
        $this->assertSame(500.0, $group->getMinMargin());
    }
}
