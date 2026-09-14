<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Mgr\GroupTrash;
use PHPUnit\Framework\TestCase;

final class GroupTrashTest extends TestCase
{
    /** @var array<int,array<string,mixed>> */
    private $groups = [
        1 => ['id' => 1, 'parent_id' => null, 'title' => 'Root'],
        10 => ['id' => 10, 'parent_id' => 1, 'title' => 'Child A'],
        11 => ['id' => 11, 'parent_id' => 1, 'title' => 'Child B', 'deleted_at' => '2026-01-01 12:00:00'],
        2 => ['id' => 2, 'parent_id' => null, 'title' => 'Other root'],
    ];

    public function testSoftDeleteSubgroupMovesProductsToParent(): void
    {
        $plan = GroupTrash::planSoftDelete($this->groups[10], $this->groups);

        $this->assertSame([10], $plan['softIds']);
        $this->assertSame([10 => 1], $plan['productMoves']);
    }

    public function testSoftDeleteRootIncludesChildrenAndUnassignsProducts(): void
    {
        $plan = GroupTrash::planSoftDelete($this->groups[1], $this->groups);

        $this->assertEqualsCanonicalizing([1, 10, 11], $plan['softIds']);
        $this->assertSame([1 => null, 10 => null, 11 => null], $plan['productMoves']);
    }

    public function testPurgeSubgroupMovesToActiveParent(): void
    {
        $deleted = $this->groups[10] + ['deleted_at' => '2026-01-01 12:00:00'];
        $plan = GroupTrash::planPurge($deleted, $this->groups);

        $this->assertSame([10], $plan['purgeIds']);
        $this->assertSame([10 => 1], $plan['productMoves']);
    }

    public function testPurgeSubgroupUnassignsWhenParentDeleted(): void
    {
        $all = $this->groups;
        $all[1]['deleted_at'] = '2026-01-01 12:00:00';
        $deleted = $all[10] + ['deleted_at' => '2026-01-01 12:00:00'];
        $plan = GroupTrash::planPurge($deleted, $all);

        $this->assertSame([10 => null], $plan['productMoves']);
    }

    public function testPurgeRootDeletesChildren(): void
    {
        $deleted = $this->groups[1] + ['deleted_at' => '2026-01-01 12:00:00'];
        $plan = GroupTrash::planPurge($deleted, $this->groups);

        $this->assertEqualsCanonicalizing([1, 10, 11], $plan['purgeIds']);
        $this->assertSame([1 => null, 10 => null, 11 => null], $plan['productMoves']);
    }

    public function testRestoreSubgroupOnlySelf(): void
    {
        $deleted = $this->groups[10] + ['deleted_at' => '2026-01-01 12:00:00'];
        $ids = GroupTrash::planRestore($deleted, $this->groups);

        $this->assertSame([10], $ids);
    }

    public function testRestoreRootIncludesDeletedChildren(): void
    {
        $deleted = $this->groups[1] + ['deleted_at' => '2026-01-01 12:00:00'];
        $ids = GroupTrash::planRestore($deleted, $this->groups);

        $this->assertEqualsCanonicalizing([1, 11], $ids);
    }

    public function testIsDeleted(): void
    {
        $this->assertFalse(GroupTrash::isDeleted(['deleted_at' => null]));
        $this->assertTrue(GroupTrash::isDeleted(['deleted_at' => '2026-01-01 12:00:00']));
    }
}
