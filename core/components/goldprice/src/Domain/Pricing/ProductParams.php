<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

/**
 * Immutable pricing parameters of a product (goldprice_product row).
 *
 * buyout_price is used only with ignore_market (manual ₽). In market mode it is
 * ignored: legacy rows mix units and cannot drive the formula.
 */
final class ProductParams
{
    /** @var float grams */
    private $weight;

    /** @var string ProductCatalog::COIN_* or '' */
    private $coinType;

    /** @var bool */
    private $useCustom;

    /** @var float percent of base for sale when useCustom */
    private $customPct;

    /** @var float percent of base for buyout when useCustom and non-zero */
    private $customBuyPct;

    /** @var float rubles added to sale price when useCustom */
    private $customFix;

    /** @var float rubles added to buyout when useCustom (may be negative) */
    private $customBuyFix;

    /** @var bool */
    private $ignoreMarket;

    /** @var float manual sale price used when ignoreMarket */
    private $fixedPrice;

    /** @var float manual buyout in ₽ used when ignoreMarket */
    private $buyoutPrice;

    private function __construct(
        float $weight,
        string $coinType,
        bool $useCustom,
        float $customPct,
        float $customBuyPct,
        float $customFix,
        float $customBuyFix,
        bool $ignoreMarket,
        float $fixedPrice,
        float $buyoutPrice
    ) {
        if ($weight < 0) {
            throw new \InvalidArgumentException('Product weight cannot be negative');
        }
        if ($fixedPrice < 0) {
            throw new \InvalidArgumentException('fixed_price cannot be negative');
        }
        if ($buyoutPrice < 0) {
            throw new \InvalidArgumentException('buyout_price cannot be negative');
        }

        $this->weight = $weight;
        $this->coinType = $coinType;
        $this->useCustom = $useCustom;
        $this->customPct = $customPct;
        $this->customBuyPct = $customBuyPct;
        $this->customFix = $customFix;
        $this->customBuyFix = $customBuyFix;
        $this->ignoreMarket = $ignoreMarket;
        $this->fixedPrice = $fixedPrice;
        $this->buyoutPrice = $buyoutPrice;
    }

    /**
     * @param array $row goldprice_product row (xPDO toArray or validated form data)
     */
    public static function fromRow(array $row): self
    {
        return new self(
            self::float($row, 'weight'),
            isset($row['coin_type']) ? trim((string) $row['coin_type']) : '',
            self::bool($row, 'use_custom'),
            self::float($row, 'custom_pct'),
            self::float($row, 'custom_buy_pct'),
            self::float($row, 'custom_fix'),
            self::float($row, 'custom_buy_fix'),
            self::bool($row, 'ignore_market'),
            self::float($row, 'fixed_price'),
            self::float($row, 'buyout_price')
        );
    }

    public function getWeight(): float
    {
        return $this->weight;
    }

    public function getCoinType(): string
    {
        return $this->coinType;
    }

    public function isUseCustom(): bool
    {
        return $this->useCustom;
    }

    public function getCustomPct(): float
    {
        return $this->customPct;
    }

    public function getCustomBuyPct(): float
    {
        return $this->customBuyPct;
    }

    public function getCustomFix(): float
    {
        return $this->customFix;
    }

    public function getCustomBuyFix(): float
    {
        return $this->customBuyFix;
    }

    public function isIgnoreMarket(): bool
    {
        return $this->ignoreMarket;
    }

    public function getFixedPrice(): float
    {
        return $this->fixedPrice;
    }

    public function getBuyoutPrice(): float
    {
        return $this->buyoutPrice;
    }

    private static function float(array $row, string $key): float
    {
        return isset($row[$key]) ? (float) $row[$key] : 0.0;
    }

    private static function bool(array $row, string $key): bool
    {
        return isset($row[$key]) && (bool) $row[$key] && (string) $row[$key] !== '0';
    }
}
