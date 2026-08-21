<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * Match product weight to a weight group within percent tolerance.
 */
final class WeightGroupMatcher
{
    /**
     * @param array<int,float> $groups id => weight_grams
     * @return int|null group id
     */
    public static function match(float $weight, array $groups, float $tolerancePct)
    {
        if ($weight <= 0 || $groups === []) {
            return null;
        }

        $tolerancePct = max(0.0, $tolerancePct);
        $bestId = null;
        $bestDelta = null;

        foreach ($groups as $id => $groupWeight) {
            $groupWeight = (float) $groupWeight;
            if ($groupWeight <= 0) {
                continue;
            }
            $delta = abs($weight - $groupWeight);
            $allowed = $groupWeight * ($tolerancePct / 100.0);
            if ($delta > $allowed) {
                continue;
            }
            if ($bestDelta === null || $delta < $bestDelta) {
                $bestDelta = $delta;
                $bestId = (int) $id;
            }
        }

        return $bestId;
    }
}
