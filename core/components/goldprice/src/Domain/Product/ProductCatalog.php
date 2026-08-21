<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * Known catalog metals / coin types for TV migration and form validation.
 */
final class ProductCatalog
{
    public const METALS = ['золото', 'серебро'];

    /** Investment coins are VAT-free, commemorative ones are not (ТЗ п.10). */
    public const COIN_INVESTMENT = 'инвестиционные';

    public const COIN_COMMEMORATIVE = 'памятные';

    public const COIN_TYPES = [self::COIN_INVESTMENT, self::COIN_COMMEMORATIVE];

    /** custom_pct bounds (decimal 8,4) */
    public const PCT_MIN = -100.0;
    public const PCT_MAX = 9999.0;
}
