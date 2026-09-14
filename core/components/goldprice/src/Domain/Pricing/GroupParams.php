<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

/**
 * Immutable pricing parameters of a weight group (goldprice_group row).
 *
 * price_step / stoploss are Step 6-7 concerns and stay out of the price engine.
 */
final class GroupParams
{
    /** @var float percent added to cost on sale */
    private $saleMarkupPct;

    /** @var float rubles added to sale price */
    private $saleFix;

    /** @var float percent subtracted from cost on buyout */
    private $buyDiscountPct;

    /** @var float rubles subtracted from buy price */
    private $buyFix;

    /** @var float minimum sale − buy gap in rubles */
    private $minMargin;

    /** @var string e.g. подгруппы «Кенгуру»; empty for root */
    private $label;

    private function __construct(
        float $saleMarkupPct,
        float $saleFix,
        float $buyDiscountPct,
        float $buyFix,
        float $minMargin,
        string $label = ''
    ) {
        if ($minMargin < 0) {
            throw new \InvalidArgumentException('min_margin cannot be negative');
        }

        $this->saleMarkupPct = $saleMarkupPct;
        $this->saleFix = $saleFix;
        $this->buyDiscountPct = $buyDiscountPct;
        $this->buyFix = $buyFix;
        $this->minMargin = $minMargin;
        $this->label = $label;
    }

    /**
     * @param array $row goldprice_group row (xPDO toArray or seed array)
     * @param array|null $parentRow root group row when $row is a subgroup
     */
    public static function resolve(array $row, ?array $parentRow): self
    {
        if ($parentRow === null) {
            return new self(
                self::float($row, 'sale_markup'),
                self::float($row, 'sale_fix'),
                self::float($row, 'buy_discount'),
                self::float($row, 'buy_fix'),
                self::float($row, 'min_margin'),
                ''
            );
        }

        $title = trim((string) ($row['title'] ?? ''));
        $label = $title !== '' ? 'подгруппы «' . $title . '»' : 'подгруппы';

        if (!empty($row['add_to_parent'])) {
            return new self(
                self::float($parentRow, 'sale_markup') + self::float($row, 'sale_markup'),
                self::float($parentRow, 'sale_fix') + self::float($row, 'sale_fix'),
                self::float($parentRow, 'buy_discount') + self::float($row, 'buy_discount'),
                self::float($parentRow, 'buy_fix') + self::float($row, 'buy_fix'),
                self::float($parentRow, 'min_margin'),
                $label
            );
        }

        return new self(
            self::float($row, 'sale_markup'),
            self::float($row, 'sale_fix'),
            self::float($row, 'buy_discount'),
            self::float($row, 'buy_fix'),
            self::float($row, 'min_margin'),
            $label
        );
    }

    /**
     * @param array $row goldprice_group row (xPDO toArray or seed array)
     */
    public static function fromRow(array $row): self
    {
        return self::resolve($row, null);
    }

    public function getSaleMarkupPct(): float
    {
        return $this->saleMarkupPct;
    }

    public function getSaleFix(): float
    {
        return $this->saleFix;
    }

    public function getBuyDiscountPct(): float
    {
        return $this->buyDiscountPct;
    }

    public function getBuyFix(): float
    {
        return $this->buyFix;
    }

    public function getMinMargin(): float
    {
        return $this->minMargin;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    private static function float(array $row, string $key): float
    {
        return isset($row[$key]) ? (float) $row[$key] : 0.0;
    }
}
