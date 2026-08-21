<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

/**
 * Immutable Profinance quote snapshot.
 * xauUsd / bid / ask — USD per troy ounce; usdRub — RUB per USD.
 */
final class Quote
{
    /** Site templates use 31.1 g/oz for gold↔gram math. */
    public const OZ_GRAMS = 31.1;

    /** @var float */
    private $xauUsd;

    /** @var float */
    private $usdRub;

    /** @var float */
    private $bid;

    /** @var float */
    private $ask;

    /** @var float Absolute XAUUSD session change (USD/oz) */
    private $netchangeGold;

    /** @var float Absolute USDRUB session change (RUB) */
    private $netchangeUsd;

    /** @var float */
    private $netchangePct;

    /** @var float */
    private $usdNetchangePct;

    /** @var int Gold jutcdt as unix seconds — identity / created_at */
    private $quotedAt;

    /** @var int USDRUB jutcdt as unix seconds */
    private $usdQuotedAt;

    /** @var string */
    private $raw;

    public function __construct(
        float $xauUsd,
        float $usdRub,
        float $bid,
        float $ask,
        float $netchangeGold,
        float $netchangeUsd,
        float $netchangePct,
        float $usdNetchangePct,
        int $quotedAt,
        int $usdQuotedAt,
        string $raw
    ) {
        $this->xauUsd = $xauUsd;
        $this->usdRub = $usdRub;
        $this->bid = $bid;
        $this->ask = $ask;
        $this->netchangeGold = $netchangeGold;
        $this->netchangeUsd = $netchangeUsd;
        $this->netchangePct = $netchangePct;
        $this->usdNetchangePct = $usdNetchangePct;
        $this->quotedAt = $quotedAt;
        $this->usdQuotedAt = $usdQuotedAt;
        $this->raw = $raw;
    }

    public function getXauUsd(): float
    {
        return $this->xauUsd;
    }

    public function getUsdRub(): float
    {
        return $this->usdRub;
    }

    public function getBid(): float
    {
        return $this->bid;
    }

    public function getAsk(): float
    {
        return $this->ask;
    }

    public function getNetchangeGold(): float
    {
        return $this->netchangeGold;
    }

    public function getNetchangeUsd(): float
    {
        return $this->netchangeUsd;
    }

    public function getNetchangePct(): float
    {
        return $this->netchangePct;
    }

    public function getUsdNetchangePct(): float
    {
        return $this->usdNetchangePct;
    }

    public function getQuotedAt(): int
    {
        return $this->quotedAt;
    }

    public function getUsdQuotedAt(): int
    {
        return $this->usdQuotedAt;
    }

    public function getRaw(): string
    {
        return $this->raw;
    }

    /** RUB per gram — same units as historical ClientConfig `gold` (MOEX GLDRUB_TOM). */
    public function goldRubPerGram(): float
    {
        return round($this->goldRubPerGramRaw(), 2);
    }

    /** Unrounded ₽/g — price engine must not round before multiplying by weight. */
    public function goldRubPerGramRaw(): float
    {
        return ($this->xauUsd / self::OZ_GRAMS) * $this->usdRub;
    }

    /**
     * Absolute ₽/g change vs previous session close (KursMetall2 gold2 semantics).
     * prevXau = xau - netchangeGold; prevUsd = usd - netchangeUsd.
     */
    public function goldDeltaRub(): float
    {
        $prevXau = $this->xauUsd - $this->netchangeGold;
        $prevUsd = $this->usdRub - $this->netchangeUsd;
        $current = $this->goldRubPerGramRaw();
        $previous = ($prevXau / self::OZ_GRAMS) * $prevUsd;

        return round($current - $previous, 2);
    }

    public function isGoldFresh(int $maxAgeSeconds, int $now): bool
    {
        return ($now - $this->quotedAt) <= $maxAgeSeconds;
    }

    public function isUsdFresh(int $maxAgeSeconds, int $now): bool
    {
        return ($now - $this->usdQuotedAt) <= $maxAgeSeconds;
    }

    /**
     * Rebuild from a goldprice_quote row. Prefers raw JSON so session deltas match the live parse.
     *
     * @param array<string,mixed> $row
     */
    public static function fromRow(array $row): self
    {
        $raw = (string) ($row['raw'] ?? '');
        if ($raw !== '') {
            return (new ProfinanceResponseParser(['gold', 'USDRUB']))
                ->parse($raw, PHP_INT_MAX, PHP_INT_MAX, time());
        }

        $timestamp = strtotime((string) ($row['created_at'] ?? '')) ?: 0;

        return new self(
            (float) ($row['xau_usd'] ?? 0),
            (float) ($row['usd_rub'] ?? 0),
            (float) ($row['bid'] ?? 0),
            (float) ($row['ask'] ?? 0),
            0.0,
            0.0,
            (float) ($row['netchange_pct'] ?? 0),
            0.0,
            $timestamp,
            $timestamp,
            $raw
        );
    }

    /**
     * Widget / ClientConfig keys. Strings avoid serialize_precision noise.
     *
     * @return array{gold:string,gold2:string,usd:string,usd2:string}
     */
    public function storefrontRates(): array
    {
        return [
            'gold' => number_format($this->goldRubPerGram(), 2, '.', ''),
            'gold2' => number_format($this->goldDeltaRub(), 2, '.', ''),
            'usd' => number_format($this->getUsdRub(), 4, '.', ''),
            'usd2' => number_format($this->getNetchangeUsd(), 4, '.', ''),
        ];
    }
}
