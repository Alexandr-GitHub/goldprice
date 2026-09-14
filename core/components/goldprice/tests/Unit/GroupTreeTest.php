<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Mgr\GroupTree;
use PHPUnit\Framework\TestCase;

final class GroupTreeTest extends TestCase
{
    public function testRootIdForRoot(): void
    {
        $groups = [
            3 => ['id' => 3, 'parent_id' => null, 'weight' => 7.78],
        ];

        $this->assertSame(3, GroupTree::rootId(3, $groups));
    }

    public function testRootIdForSubgroup(): void
    {
        $groups = [
            3 => ['id' => 3, 'parent_id' => null, 'weight' => 7.78],
            10 => ['id' => 10, 'parent_id' => 3, 'weight' => 7.78, 'title' => 'Кенгуру'],
        ];

        $this->assertSame(3, GroupTree::rootId(10, $groups));
    }

    public function testRootIdForBrokenParentId(): void
    {
        $groups = [
            10 => ['id' => 10, 'parent_id' => 99, 'weight' => 7.78],
        ];

        $this->assertSame(10, GroupTree::rootId(10, $groups));
    }

    public function testOrderPlacesChildrenAfterParent(): void
    {
        $ordered = GroupTree::order([
            ['id' => 10, 'parent_id' => 3, 'weight' => 7.78, 'title' => 'B'],
            ['id' => 3, 'parent_id' => null, 'weight' => 7.78, 'title' => '1/4'],
            ['id' => 1, 'parent_id' => null, 'weight' => 31.1, 'title' => '1 oz'],
            ['id' => 11, 'parent_id' => 3, 'weight' => 7.78, 'title' => 'A'],
        ]);

        $this->assertSame([3, 11, 10, 1], array_map(static function (array $row): int {
            return (int) $row['id'];
        }, $ordered));
    }
}
