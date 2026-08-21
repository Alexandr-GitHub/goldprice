<?php
declare(strict_types=1);

namespace GoldPrice\Service;

use GoldPrice;
use GoldPrice\Domain\Quote\Quote;

/**
 * Prices and the latest quote are loaded separately (static-cached per HTTP request).
 */
final class StorefrontPriceLoader
{
    /** @var array<int,array<string,mixed>>|null */
    private static $rows;

    /** @var bool */
    private static $quoteRowLoaded = false;

    /** @var array<string,mixed>|null */
    private static $quoteRow;

    /** @var Quote|null|false */
    private static $quote = false;

    /**
     * @return array<string,mixed>|null
     */
    public static function row(\modX $modx, string $corePath, int $productId)
    {
        self::ensurePrices($modx, $corePath);

        return self::$rows[$productId] ?? null;
    }

    public static function quoteAt(\modX $modx, string $corePath): ?string
    {
        self::ensureQuote($modx, $corePath);
        if (self::$quoteRow === null) {
            return null;
        }
        $at = (string) (self::$quoteRow['created_at'] ?? '');

        return $at === '' ? null : $at;
    }

    public static function quote(\modX $modx, string $corePath): ?Quote
    {
        if (self::$quote !== false) {
            return self::$quote;
        }

        self::ensureQuote($modx, $corePath);
        if (self::$quoteRow === null) {
            self::$quote = null;

            return null;
        }

        try {
            self::$quote = Quote::fromRow(self::$quoteRow);
        } catch (\Throwable $e) {
            self::$quote = null;
        }

        return self::$quote;
    }

    private static function ensurePrices(\modX $modx, string $corePath): void
    {
        if (self::$rows !== null) {
            return;
        }

        self::$rows = [];
        if (!self::boot($modx, $corePath)) {
            return;
        }

        $ignoreMarket = [];
        foreach ($modx->getCollection('GoldPriceProduct') as $product) {
            $ignoreMarket[(int) $product->get('product_id')] = !empty($product->get('ignore_market'));
        }

        foreach ($modx->getCollection('GoldPricePrice') as $obj) {
            $pid = (int) $obj->get('product_id');
            $row = $obj->toArray();
            $row['ignore_market'] = !empty($ignoreMarket[$pid]);
            self::$rows[$pid] = $row;
        }
    }

    private static function ensureQuote(\modX $modx, string $corePath): void
    {
        if (self::$quoteRowLoaded) {
            return;
        }

        self::$quoteRowLoaded = true;
        self::$quoteRow = null;
        if (!self::boot($modx, $corePath)) {
            return;
        }

        $query = $modx->newQuery('GoldPriceQuote');
        $query->sortby('created_at', 'DESC');
        $query->limit(1);
        $quotes = $modx->getCollection('GoldPriceQuote', $query);
        $latest = $quotes ? reset($quotes) : null;
        self::$quoteRow = $latest ? $latest->toArray() : null;
    }

    private static function boot(\modX $modx, string $corePath): bool
    {
        $gp = new GoldPrice($modx, ['core_path' => $corePath]);

        return $gp->initialize();
    }
}
