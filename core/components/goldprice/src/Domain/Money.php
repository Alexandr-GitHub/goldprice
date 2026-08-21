<?php
declare(strict_types=1);

namespace GoldPrice\Domain;

/**
 * Money helpers — storefront amounts are whole rubles.
 */
final class Money
{
    public static function roundMoney(float $amount): string
    {
        return number_format(round($amount, 0), 2, '.', '');
    }

    public static function toKopecks(float $amount): int
    {
        return (int) round($amount * 100);
    }

    public static function compareKopecks(int $left, int $right): int
    {
        return $left <=> $right;
    }
}
