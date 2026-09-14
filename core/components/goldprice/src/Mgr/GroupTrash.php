<?php
declare(strict_types=1);

namespace GoldPrice\Mgr;

/**
 * Soft-delete / restore / purge plans for weight groups. No $modx in planners.
 */
final class GroupTrash
{
    /**
     * @param array<string,mixed> $groupRow
     * @param array<int,array<string,mixed>> $allGroupsById
     * @return array{softIds:int[], productMoves:array<int,int|null>}
     */
    public static function planSoftDelete(array $groupRow, array $allGroupsById): array
    {
        $id = (int) ($groupRow['id'] ?? 0);
        $parentId = isset($groupRow['parent_id']) ? (int) $groupRow['parent_id'] : 0;

        if ($parentId > 0) {
            return [
                'softIds' => [$id],
                'productMoves' => [$id => $parentId],
            ];
        }

        $softIds = [$id];
        $productMoves = [$id => null];
        foreach ($allGroupsById as $gid => $row) {
            if ((int) ($row['parent_id'] ?? 0) === $id) {
                $childId = (int) $gid;
                $softIds[] = $childId;
                $productMoves[$childId] = null;
            }
        }

        return ['softIds' => $softIds, 'productMoves' => $productMoves];
    }

    /**
     * @param array<string,mixed> $groupRow
     * @param array<int,array<string,mixed>> $allGroupsById
     * @return array{purgeIds:int[], productMoves:array<int,int|null>}
     */
    public static function planPurge(array $groupRow, array $allGroupsById): array
    {
        $id = (int) ($groupRow['id'] ?? 0);
        $parentId = isset($groupRow['parent_id']) ? (int) $groupRow['parent_id'] : 0;

        if ($parentId > 0) {
            $target = null;
            if (isset($allGroupsById[$parentId]) && !self::isDeleted($allGroupsById[$parentId])) {
                $target = $parentId;
            }

            return [
                'purgeIds' => [$id],
                'productMoves' => [$id => $target],
            ];
        }

        $purgeIds = [$id];
        $productMoves = [$id => null];
        foreach ($allGroupsById as $gid => $row) {
            if ((int) ($row['parent_id'] ?? 0) === $id) {
                $childId = (int) $gid;
                $purgeIds[] = $childId;
                $productMoves[$childId] = null;
            }
        }

        return ['purgeIds' => $purgeIds, 'productMoves' => $productMoves];
    }

    /**
     * @param array<string,mixed> $groupRow
     * @param array<int,array<string,mixed>> $allGroupsById
     * @return int[]
     */
    public static function planRestore(array $groupRow, array $allGroupsById): array
    {
        $id = (int) ($groupRow['id'] ?? 0);
        $parentId = isset($groupRow['parent_id']) ? (int) $groupRow['parent_id'] : 0;

        if ($parentId > 0) {
            return [$id];
        }

        $restoreIds = [$id];
        foreach ($allGroupsById as $gid => $row) {
            if ((int) ($row['parent_id'] ?? 0) === $id && self::isDeleted($row)) {
                $restoreIds[] = (int) $gid;
            }
        }

        return $restoreIds;
    }

    /**
     * @param array<int,int|null> $productMoves group_id from => to (null = unassign)
     */
    public static function applyProductMoves(\modX $modx, array $productMoves): void
    {
        if ($productMoves === []) {
            return;
        }

        $productTable = $modx->getTableName('GoldPriceProduct');
        foreach ($productMoves as $fromId => $toId) {
            $fromId = (int) $fromId;
            if ($fromId <= 0) {
                continue;
            }
            if ($toId === null) {
                $sql = "UPDATE {$productTable} SET `group_id` = NULL WHERE `group_id` = :from";
                $stmt = $modx->prepare($sql);
                if ($stmt) {
                    $stmt->bindValue(':from', $fromId, \PDO::PARAM_INT);
                    $stmt->execute();
                }
                continue;
            }
            $sql = "UPDATE {$productTable} SET `group_id` = :to WHERE `group_id` = :from";
            $stmt = $modx->prepare($sql);
            if ($stmt) {
                $stmt->bindValue(':to', (int) $toId, \PDO::PARAM_INT);
                $stmt->bindValue(':from', $fromId, \PDO::PARAM_INT);
                $stmt->execute();
            }
        }
    }

    /**
     * @param array<string,mixed> $row
     */
    public static function isDeleted(array $row): bool
    {
        $v = $row['deleted_at'] ?? null;

        return $v !== null && $v !== '' && $v !== '0000-00-00 00:00:00';
    }
}
