<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

/**
 * Pure Profinance JSON → Quote. No cURL, no MODX, no DB.
 */
final class ProfinanceResponseParser
{
    /** @var string[] */
    private $expectedTickers;

    /**
     * @param string[] $expectedTickers
     */
    public function __construct(array $expectedTickers)
    {
        $this->expectedTickers = array_values($expectedTickers);
    }

    /**
     * @param int|null $now Unix seconds; null = time()
     * @param int|null $sidLength SID length for diagnostics (never the SID itself)
     * @throws QuoteParseException
     * @throws QuoteStaleException
     */
    public function parse(
        string $json,
        int $goldMaxAgeSeconds,
        int $usdMaxAgeSeconds,
        ?int $now = null,
        ?int $sidLength = null
    ): Quote {
        if ($now === null) {
            $now = time();
        }

        $data = json_decode($json, true);
        if (!is_array($data) || $data === []) {
            throw new QuoteParseException('Invalid Profinance JSON');
        }

        $result = $data[0] ?? null;
        if (!is_array($result)) {
            throw new QuoteParseException('Missing Profinance result object');
        }

        // respCode arrives as string "0"
        if (!array_key_exists('respCode', $result) || (int) $result['respCode'] !== 0) {
            $code = isset($result['respCode']) ? (string) $result['respCode'] : '?';
            throw new QuoteParseException('Profinance respCode=' . $code);
        }

        // Payload array is "message" (items themselves use "msg" for type)
        $messages = isset($result['message']) && is_array($result['message'])
            ? $result['message']
            : null;
        if ($messages === null) {
            throw new QuoteParseException('Missing Profinance message payload');
        }
        if ($messages === []) {
            if ($sidLength === null) {
                $sidHint = 'SID length unknown';
            } elseif ($sidLength === 0) {
                $sidHint = 'SID is empty';
            } else {
                $sidHint = 'SID is set (length=' . $sidLength . ')';
            }
            throw new QuoteParseException(
                'Empty Profinance message — check goldprice.pf_sid (wrong/expired), '
                . 'ticker subscription, or unknown tickers; ' . $sidHint
            );
        }

        $last = [];
        $quotes = [];
        foreach ($messages as $row) {
            if (!is_array($row) || !isset($row['ticker'], $row['msg'])) {
                continue;
            }
            $ticker = (string) $row['ticker'];
            $type = (string) $row['msg'];
            if ($type === 'lastprice') {
                $last[$ticker] = $row;
            } elseif ($type === 'quote') {
                $quotes[$ticker] = $row;
            }
        }

        foreach ($this->expectedTickers as $ticker) {
            if (!isset($last[$ticker])) {
                throw new QuoteParseException('Missing ticker lastprice: ' . $ticker);
            }
        }

        if (!isset($quotes['gold'])) {
            throw new QuoteParseException('Missing ticker quote: gold');
        }

        $goldLast = $last['gold'];
        $usdLast = $last['USDRUB'];
        $goldQuote = $quotes['gold'];

        // Identity / created_at follows gold — it drives the gram price
        $goldAt = $this->readJutcdtSeconds($goldLast);
        $usdAt = $this->readJutcdtSeconds($usdLast);

        $xauUsd = $this->readFloat($goldLast, 'last');
        $usdRub = $this->readFloat($usdLast, 'last');
        $bid = $this->readFloat($goldQuote, 'bid');
        $ask = $this->readFloat($goldQuote, 'ask');
        $netchangeGold = $this->readFloat($goldLast, 'netchange');
        $netchangeUsd = $this->readFloat($usdLast, 'netchange');
        $netchangePct = $this->readPct($goldLast, 'netchangepers');
        $usdNetchangePct = $this->readPct($usdLast, 'netchangepers');

        if ($xauUsd <= 0.0 || $usdRub <= 0.0) {
            throw new QuoteParseException('Non-positive gold or USD rate');
        }

        $quote = new Quote(
            $xauUsd,
            $usdRub,
            $bid,
            $ask,
            $netchangeGold,
            $netchangeUsd,
            $netchangePct,
            $usdNetchangePct,
            $goldAt,
            $usdAt,
            $json
        );

        $staleParts = [];
        $goldAge = $now - $goldAt;
        $usdAge = $now - $usdAt;
        if ($goldAge > $goldMaxAgeSeconds) {
            $staleParts[] = 'gold age=' . $goldAge . 's > max=' . $goldMaxAgeSeconds . 's';
        }
        if ($usdAge > $usdMaxAgeSeconds) {
            $staleParts[] = 'USDRUB age=' . $usdAge . 's > max=' . $usdMaxAgeSeconds . 's';
        }

        if ($staleParts !== []) {
            throw new QuoteStaleException(
                'Stale quote: ' . implode('; ', $staleParts)
                . '; gold_jutcdt=' . $goldAt
                . ' usd_jutcdt=' . $usdAt,
                $quote
            );
        }

        return $quote;
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readJutcdtSeconds(array $row): int
    {
        if (!isset($row['jutcdt']) || !is_numeric($row['jutcdt'])) {
            throw new QuoteParseException('Missing jutcdt');
        }

        return (int) floor(((float) $row['jutcdt']) / 1000.0);
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readFloat(array $row, string $key): float
    {
        if (!isset($row[$key]) || !is_numeric($row[$key])) {
            throw new QuoteParseException('Missing numeric field: ' . $key);
        }

        return (float) $row[$key];
    }

    /**
     * @param array<string,mixed> $row
     */
    private function readPct(array $row, string $key): float
    {
        if (!isset($row[$key])) {
            throw new QuoteParseException('Missing field: ' . $key);
        }
        $raw = str_replace('%', '', (string) $row[$key]);
        if (!is_numeric($raw)) {
            throw new QuoteParseException('Non-numeric percent: ' . $key);
        }

        return (float) $raw;
    }
}
