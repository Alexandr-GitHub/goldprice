<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Quote\FixtureQuoteProvider;
use GoldPrice\Domain\Quote\QuoteProviderInterface;
use PHPUnit\Framework\TestCase;

final class FixtureQuoteProviderTest extends TestCase
{
    private const FIXTURE_TS = 1787140846;

    public function testImplementsInterfaceAndReturnsQuote(): void
    {
        $path = dirname(__DIR__) . '/Fixtures/profinance_ok.json';
        $provider = new FixtureQuoteProvider($path, ['gold', 'USDRUB'], 900, self::FIXTURE_TS);

        $this->assertInstanceOf(QuoteProviderInterface::class, $provider);
        $quote = $provider->fetchQuote();
        $this->assertSame(4365.23, $quote->getXauUsd());
        $this->assertSame(85.07, $quote->getUsdRub());
    }
}
