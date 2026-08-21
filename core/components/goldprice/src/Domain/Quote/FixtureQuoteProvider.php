<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

/**
 * Reads a fixture file — for unit tests and offline staging dry-runs.
 */
final class FixtureQuoteProvider implements QuoteProviderInterface
{
    /** @var string */
    private $path;

    /** @var ProfinanceResponseParser */
    private $parser;

    /** @var int */
    private $goldMaxAgeSeconds;

    /** @var int */
    private $usdMaxAgeSeconds;

    /** @var int|null */
    private $now;

    /**
     * @param string[] $expectedTickers
     * @param int|null $now Fixed clock for deterministic freshness checks
     */
    public function __construct(
        string $path,
        array $expectedTickers,
        int $goldMaxAgeSeconds = 900,
        ?int $now = null,
        int $usdMaxAgeSeconds = 86400
    ) {
        $this->path = $path;
        $this->parser = new ProfinanceResponseParser($expectedTickers);
        $this->goldMaxAgeSeconds = $goldMaxAgeSeconds;
        $this->usdMaxAgeSeconds = $usdMaxAgeSeconds;
        $this->now = $now;
    }

    public function fetchQuote(): Quote
    {
        if (!is_file($this->path) || !is_readable($this->path)) {
            throw new QuoteParseException('Fixture not readable: ' . basename($this->path));
        }
        $json = file_get_contents($this->path);
        if ($json === false || $json === '') {
            throw new QuoteParseException('Empty fixture: ' . basename($this->path));
        }

        return $this->parser->parse(
            $json,
            $this->goldMaxAgeSeconds,
            $this->usdMaxAgeSeconds,
            $this->now
        );
    }
}
