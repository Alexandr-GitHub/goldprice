<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Quote\ProfinanceResponseParser;
use GoldPrice\Domain\Quote\Quote;
use PHPUnit\Framework\TestCase;

final class WidgetQuotesTest extends TestCase
{
    private const FIXTURE_TS = 1787140846;

    public function testFromRowFixtureMatchesQuoteMethods(): void
    {
        $raw = $this->fixture('profinance_ok.json');
        $direct = (new ProfinanceResponseParser(['gold', 'USDRUB']))
            ->parse($raw, PHP_INT_MAX, PHP_INT_MAX, self::FIXTURE_TS);

        $row = [
            'created_at' => date('Y-m-d H:i:s', $direct->getQuotedAt()),
            'xau_usd' => $direct->getXauUsd(),
            'usd_rub' => $direct->getUsdRub(),
            'bid' => $direct->getBid(),
            'ask' => $direct->getAsk(),
            'netchange_pct' => $direct->getNetchangePct(),
            'source' => 'profinance',
            'raw' => $raw,
        ];
        $fromRow = Quote::fromRow($row);
        $payload = $fromRow->storefrontRates();

        $this->assertSame($direct->goldRubPerGram(), $fromRow->goldRubPerGram());
        $this->assertSame($direct->goldDeltaRub(), $fromRow->goldDeltaRub());
        $this->assertSame($direct->getUsdRub(), $fromRow->getUsdRub());
        $this->assertSame($direct->getNetchangeUsd(), $fromRow->getNetchangeUsd());

        $this->assertSame(number_format($direct->goldRubPerGram(), 2, '.', ''), $payload['gold']);
        $this->assertSame(number_format($direct->goldDeltaRub(), 2, '.', ''), $payload['gold2']);
        $this->assertSame(number_format($direct->getUsdRub(), 4, '.', ''), $payload['usd']);
        $this->assertSame(number_format($direct->getNetchangeUsd(), 4, '.', ''), $payload['usd2']);
    }

    public function testFromRowWithoutRawUsesColumnsAndZeroDeltas(): void
    {
        $quote = Quote::fromRow([
            'created_at' => '2026-08-20 12:00:00',
            'xau_usd' => 4493.30,
            'usd_rub' => 83.698,
            'bid' => 4493.0,
            'ask' => 4493.5,
            'netchange_pct' => 3.76,
            'source' => 'profinance',
            'raw' => '',
        ]);

        $this->assertSame(12092.61, $quote->goldRubPerGram());
        $this->assertSame(0.0, $quote->goldDeltaRub());
        $this->assertSame(0.0, $quote->getNetchangeUsd());
        $payload = $quote->storefrontRates();
        $this->assertSame('12092.61', $payload['gold']);
        $this->assertSame('0.00', $payload['gold2']);
        $this->assertSame('83.6980', $payload['usd']);
        $this->assertSame('0.0000', $payload['usd2']);
    }

    public function testSnippetDoesNotLoadPriceRows(): void
    {
        $src = file_get_contents(dirname(__DIR__, 2) . '/elements/snippets/gpQuotes.php');
        $this->assertNotFalse($src);
        $this->assertStringContainsString('StorefrontPriceLoader::quote', $src);
        $this->assertStringNotContainsString('StorefrontPriceLoader::row', $src);
        $this->assertStringNotContainsString('GoldPricePrice', $src);
    }

    public function testQuoteGetListStripsRaw(): void
    {
        $src = file_get_contents(dirname(__DIR__, 2) . '/processors/mgr/quote/getlist.class.php');
        $this->assertNotFalse($src);
        $this->assertStringContainsString("unset(\$row['raw'])", $src);
        $this->assertStringContainsString('Quote::fromRow', $src);
    }

    private function fixture(string $name): string
    {
        $path = dirname(__DIR__) . '/Fixtures/' . $name;
        $json = file_get_contents($path);
        $this->assertNotFalse($json);

        return $json;
    }
}
