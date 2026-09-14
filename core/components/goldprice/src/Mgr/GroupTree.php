<?php
declare(strict_types=1);

namespace GoldPrice\Mgr;

/**
 * Weight group / pricing subgroup helpers. No $modx.
 */
final class GroupTree
{
    /**
     * @param array<int,array<string,mixed>> $groupsById
     */
    public static function rootId(int $id, array $groupsById): int
    {
        if ($id <= 0 || !isset($groupsById[$id])) {
            return $id;
        }

        $seen = [];
        while ($id > 0 && isset($groupsById[$id]) && !isset($seen[$id])) {
            $seen[$id] = true;
            $parentId = isset($groupsById[$id]['parent_id']) ? (int) $groupsById[$id]['parent_id'] : 0;
            if ($parentId <= 0) {
                return $id;
            }
            if (!isset($groupsById[$parentId])) {
                return $id;
            }
            $id = $parentId;
        }

        return $id;
    }

    /**
     * Parent row first, then its children (stable weight order among roots).
     *
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public static function order(array $rows): array
    {
        if ($rows === []) {
            return [];
        }

        $byId = [];
        foreach ($rows as $row) {
            if (!isset($row['id'])) {
                continue;
            }
            $byId[(int) $row['id']] = $row;
        }

        $roots = [];
        $children = [];
        foreach ($byId as $id => $row) {
            $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
            if ($parentId > 0 && isset($byId[$parentId])) {
                $children[$parentId][] = $row;
            } else {
                $roots[] = $row;
            }
        }

        usort($roots, static function (array $a, array $b): int {
            $wa = (float) ($a['weight'] ?? 0);
            $wb = (float) ($b['weight'] ?? 0);
            if ($wa === $wb) {
                return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
            }

            return $wa <=> $wb;
        });

        $out = [];
        foreach ($roots as $root) {
            $rootId = (int) $root['id'];
            $out[] = $root;
            if (!isset($children[$rootId])) {
                continue;
            }
            usort($children[$rootId], static function (array $a, array $b): int {
                $ta = (string) ($a['title'] ?? '');
                $tb = (string) ($b['title'] ?? '');
                if ($ta === $tb) {
                    return ((int) ($a['id'] ?? 0)) <=> ((int) ($b['id'] ?? 0));
                }

                return strcasecmp($ta, $tb);
            });
            foreach ($children[$rootId] as $child) {
                $out[] = $child;
            }
        }

        return $out;
    }
}
