<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Quote\ProfinanceResponseParser;
use GoldPrice\Domain\Quote\Quote;
use GoldPrice\Domain\Quote\QuoteParseException;
use GoldPrice\Domain\Quote\QuoteStaleException;
use PHPUnit\Framework\TestCase;

final class ProfinanceResponseParserTest extends TestCase
{
    /** Same jutcdt on both tickers in profinance_ok.json */
    private const FIXTURE_TS = 1787140846;

    /** Real live split (UTC): gold 17:24:17, USDRUB 15:59:52 — FX session closed */
    private const LIVE_GOLD_TS = 1787160257;
    private const LIVE_USD_TS = 1787155192;

    private const OZ_GRAMS = 31.1;
    private const GOLD_MAX = 900;
    private const USD_MAX = 86400;

    /** @var ProfinanceResponseParser */
    private $parser;

    protected function setUp(): void
    {
        $this->parser = new ProfinanceResponseParser(['gold', 'USDRUB']);
    }

    public function testParsesOkFixtureWithStringRespCode(): void
    {
        $json = $this->fixture('profinance_ok.json');
        $quote = $this->parser->parse($json, self::GOLD_MAX, self::USD_MAX, self::FIXTURE_TS);

        $this->assertSame(4365.23, $quote->getXauUsd());
        $this->assertSame(85.07, $quote->getUsdRub());
        $this->assertSame(4364.9, $quote->getBid());
        $this->assertSame(4365.56, $quote->getAsk());
        $this->assertSame(0.71, $quote->getNetchangePct());
        $this->assertSame(0.14, $quote->getUsdNetchangePct());
        $this->assertSame(30.7, $quote->getNetchangeGold());
        $this->assertSame(0.12, $quote->getNetchangeUsd());
        $this->assertSame(self::FIXTURE_TS, $quote->getQuotedAt());
        $this->assertSame(self::FIXTURE_TS, $quote->getUsdQuotedAt());
        $this->assertSame($json, $quote->getRaw());

        $expectedGold = round((4365.23 / self::OZ_GRAMS) * 85.07, 2);
        $this->assertSame($expectedGold, $quote->goldRubPerGram());
    }

    public function testEmptyMessageIsError(): void
    {
        $this->expectException(QuoteParseException::class);
        $this->expectExceptionMessageMatches('/pf_sid|ticker subscription|unknown tickers/');
        $this->parser->parse(
            $this->fixture('profinance_empty_msg.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS,
            20
        );
    }

    public function testEmptyMessageIncludesSidLengthNotValue(): void
    {
        try {
            $this->parser->parse(
                $this->fixture('profinance_empty_msg.json'),
                self::GOLD_MAX,
                self::USD_MAX,
                self::FIXTURE_TS,
                19
            );
            $this->fail('Expected QuoteParseException');
        } catch (QuoteParseException $e) {
            $this->assertStringContainsString('length=19', $e->getMessage());
            $this->assertStringNotContainsString('SqLri', $e->getMessage());
        }
    }

    public function testMissingExpectedTickerIsError(): void
    {
        $this->expectException(QuoteParseException::class);
        $this->parser->parse(
            $this->fixture('profinance_missing_usd.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS
        );
    }

    public function testBrokenJsonIsError(): void
    {
        $this->expectException(QuoteParseException::class);
        $this->parser->parse(
            $this->fixture('profinance_bad.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS
        );
    }

    public function testStaleGoldJutcdtIsError(): void
    {
        $this->expectException(QuoteStaleException::class);
        $this->expectExceptionMessageMatches('/gold age=/');
        $this->parser->parse(
            $this->fixture('profinance_ok.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS + 901
        );
    }

    public function testFreshnessBoundaryInclusiveForGold(): void
    {
        $atBoundary = $this->parser->parse(
            $this->fixture('profinance_ok.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS + 900
        );
        $this->assertSame(self::FIXTURE_TS, $atBoundary->getQuotedAt());

        $this->expectException(QuoteStaleException::class);
        $this->parser->parse(
            $this->fixture('profinance_ok.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            self::FIXTURE_TS + 901
        );
    }

    /**
     * Regression: USDRUB stopped at FX close (~67–83 min older than gold) must still pass
     * with defaults gold_max=900 / usd_max=86400 when gold itself is fresh.
     */
    public function testLiveSplitUsdOlderThanGoldButWithinUsdMaxIsOk(): void
    {
        $now = self::LIVE_GOLD_TS + 100;
        $usdAge = $now - self::LIVE_USD_TS;
        $this->assertGreaterThan(self::GOLD_MAX, $usdAge, 'precondition: USD older than gold max age');
        $this->assertLessThan(self::USD_MAX, $usdAge, 'precondition: USD within day limit');

        $quote = $this->parser->parse(
            $this->fixture('profinance_live_split.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            $now
        );

        $this->assertSame(self::LIVE_GOLD_TS, $quote->getQuotedAt());
        $this->assertSame(self::LIVE_USD_TS, $quote->getUsdQuotedAt());
        $this->assertSame(4490.1, $quote->getXauUsd());
        $this->assertSame(83.698, $quote->getUsdRub());
    }

    public function testUsdOlderThanDayIsStale(): void
    {
        $json = $this->fixture('profinance_usd_day_old.json');
        $data = json_decode($json, true);
        $goldMs = null;
        $usdMs = null;
        foreach ($data[0]['message'] as $row) {
            if ($row['msg'] !== 'lastprice') {
                continue;
            }
            if ($row['ticker'] === 'gold') {
                $goldMs = (int) floor(((float) $row['jutcdt']) / 1000.0);
            }
            if ($row['ticker'] === 'USDRUB') {
                $usdMs = (int) floor(((float) $row['jutcdt']) / 1000.0);
            }
        }
        $this->assertNotNull($goldMs);
        $this->assertNotNull($usdMs);

        $now = $goldMs + 100;
        $this->assertGreaterThan(self::USD_MAX, $now - $usdMs);

        try {
            $this->parser->parse($json, self::GOLD_MAX, self::USD_MAX, $now);
            $this->fail('Expected QuoteStaleException');
        } catch (QuoteStaleException $e) {
            $this->assertStringContainsString('USDRUB age=', $e->getMessage());
            $this->assertStringContainsString('usd_jutcdt=' . $usdMs, $e->getMessage());
            $this->assertStringContainsString('gold_jutcdt=' . $goldMs, $e->getMessage());
            $this->assertInstanceOf(Quote::class, $e->getQuote());
        }
    }

    public function testGoldOlderThanMaxIsStaleEvenIfUsdFresh(): void
    {
        $now = self::LIVE_GOLD_TS + 901;
        $usdAge = $now - self::LIVE_USD_TS;
        $this->assertLessThan(self::USD_MAX, $usdAge);

        $this->expectException(QuoteStaleException::class);
        $this->expectExceptionMessageMatches('/gold age=/');
        $this->parser->parse(
            $this->fixture('profinance_live_split.json'),
            self::GOLD_MAX,
            self::USD_MAX,
            $now
        );
    }

    public function testGoldDeltaRubMatchesCustomerExample(): void
    {
        // Customer check: XAU 4493.30 nc 162.71; USD 83.698 nc -1.272 → gold2 ≈ +260
        $quote = new Quote(
            4493.30,
            83.698,
            4493.0,
            4493.5,
            162.71,
            -1.272,
            3.76,
            -1.50,
            1787160000,
            1787155192,
            '{}'
        );

        $this->assertSame(12092.61, $quote->goldRubPerGram());
        $this->assertEqualsWithDelta(260.77, $quote->goldDeltaRub(), 0.01);
        $this->assertSame(-1.272, $quote->getNetchangeUsd());
    }

    private function fixture(string $name): string
    {
        $path = dirname(__DIR__) . '/Fixtures/' . $name;
        $json = file_get_contents($path);
        $this->assertNotFalse($json);

        return $json;
    }
}
