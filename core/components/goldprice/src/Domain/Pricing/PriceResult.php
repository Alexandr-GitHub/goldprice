<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Pricing;

use GoldPrice\Domain\Money;

/**
 * Outcome of one product price calculation.
 *
 * Money is rounded here and nowhere else, so intermediate math stays exact.
 * Missing data is a skipped result, not an exception: batch recalculation
 * must not die on a single unconfigured product.
 */
final class PriceResult
{
    /** @var bool */
    private $computable;

    /** @var string reason why the price cannot be calculated */
    private $reason;

    /** @var string */
    private $cost;

    /** @var string */
    private $salePrice;

    /** @var string|null null when buyout is not offered */
    private $buyPrice;

    /** @var string */
    private $saleCalc;

    /** @var string */
    private $buyCalc;

    /** @var string reason why buyout is not offered */
    private $buyReason;

    private function __construct(
        bool $computable,
        string $reason,
        string $cost,
        string $salePrice,
        ?string $buyPrice,
        string $saleCalc,
        string $buyCalc,
        string $buyReason
    ) {
        $this->computable = $computable;
        $this->reason = $reason;
        $this->cost = $cost;
        $this->salePrice = $salePrice;
        $this->buyPrice = $buyPrice;
        $this->saleCalc = $saleCalc;
        $this->buyCalc = $buyCalc;
        $this->buyReason = $buyReason;
    }

    public static function skipped(string $reason): self
    {
        $zero = Money::roundMoney(0.0);

        return new self(false, $reason, $zero, $zero, null, '', '', $reason);
    }

    public static function priced(
        float $cost,
        float $salePrice,
        ?float $buyPrice,
        string $saleCalc,
        string $buyCalc,
        string $buyReason = ''
    ): self {
        return new self(
            true,
            '',
            Money::roundMoney($cost),
            Money::roundMoney($salePrice),
            $buyPrice === null ? null : Money::roundMoney($buyPrice),
            $saleCalc,
            $buyCalc,
            $buyReason
        );
    }

    public function isComputable(): bool
    {
        return $this->computable;
    }

    public function getReason(): string
    {
        return $this->reason;
    }

    public function getCost(): string
    {
        return $this->cost;
    }

    public function getSalePrice(): string
    {
        return $this->salePrice;
    }

    public function getBuyPrice(): ?string
    {
        return $this->buyPrice;
    }

    public function isBuyOffered(): bool
    {
        return $this->buyPrice !== null;
    }

    public function getSaleCalc(): string
    {
        return $this->saleCalc;
    }

    public function getBuyCalc(): string
    {
        return $this->buyCalc;
    }

    public function getBuyReason(): string
    {
        return $this->buyReason;
    }
}
