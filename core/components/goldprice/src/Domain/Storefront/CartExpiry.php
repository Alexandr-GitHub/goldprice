<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Storefront;

/**
 * Price in the cart is reserved for a limited time. After that the line must go.
 */
final class CartExpiry
{
    public const STAMP = 'gp_added';
    public const DEFAULT_TTL = 3600;

    /**
     * @param array<string,mixed> $items miniShop2 cart rows keyed by cart key
     * @return array{items:array<string,mixed>,removed:int,changed:bool}
     */
    public static function apply(array $items, int $now, int $ttl): array
    {
        if ($ttl <= 0) {
            return ['items' => $items, 'removed' => 0, 'changed' => false];
        }

        $removed = 0;
        $changed = false;
        foreach ($items as $key => $item) {
            if (!is_array($item)) {
                continue;
            }
            $added = isset($item[self::STAMP]) ? (int) $item[self::STAMP] : 0;
            if ($added <= 0) {
                $items[$key][self::STAMP] = $now;
                $changed = true;
                continue;
            }
            if (($now - $added) >= $ttl) {
                unset($items[$key]);
                ++$removed;
                $changed = true;
            }
        }

        return ['items' => $items, 'removed' => $removed, 'changed' => $changed];
    }

    /**
     * @param array<string,mixed> $item
     * @return array<string,mixed>
     */
    public static function stamp(array $item, int $now): array
    {
        if (!isset($item[self::STAMP]) || (int) $item[self::STAMP] <= 0) {
            $item[self::STAMP] = $now;
        }

        return $item;
    }
}
