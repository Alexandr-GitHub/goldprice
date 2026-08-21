<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Quote\FixtureQuoteProvider;
use GoldPrice\Service\QuoteUpdater;
use PHPUnit\Framework\TestCase;

final class QuoteIdentityTest extends TestCase
{
    private const FIXTURE_TS = 1787140846;

    public function testRowIdentityUsesCreatedAtAndSource(): void
    {
        $path = dirname(__DIR__) . '/Fixtures/profinance_ok.json';
        $provider = new FixtureQuoteProvider($path, ['gold', 'USDRUB'], 900, self::FIXTURE_TS);
        $quote = $provider->fetchQuote();

        $this->assertSame(
            [
                'created_at' => date('Y-m-d H:i:s', self::FIXTURE_TS),
                'source' => 'profinance',
            ],
            QuoteUpdater::rowIdentity($quote)
        );
    }

    public function testSameQuoteYieldsSameIdentity(): void
    {
        $path = dirname(__DIR__) . '/Fixtures/profinance_ok.json';
        $provider = new FixtureQuoteProvider($path, ['gold', 'USDRUB'], 900, self::FIXTURE_TS);
        $a = QuoteUpdater::rowIdentity($provider->fetchQuote());
        $b = QuoteUpdater::rowIdentity($provider->fetchQuote());
        $this->assertSame($a, $b);
    }
}
