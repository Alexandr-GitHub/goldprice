<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Pricing\PriceDamper;
use PHPUnit\Framework\TestCase;

final class PriceDamperTest extends TestCase
{
    public function testSpecDifferenceBelowStepKeepsBothPrices(): void
    {
        $this->assertFalse(PriceDamper::shouldUpdate('217850.00', '195650.00', '218500.00', '196299.00', 1000.0));
    }

    /**
     * @dataProvider thresholdProvider
     */
    public function testEitherSideAtOrAboveStepUpdatesBoth(string $sale, string $buy): void
    {
        $this->assertTrue(PriceDamper::shouldUpdate('217850.00', '195650.00', $sale, $buy, 1000.0));
    }

    public function thresholdProvider(): array
    {
        return [
            'sale equals step' => ['218850.00', '195650.00'],
            'buy exceeds step' => ['217850.00', '196651.00'],
        ];
    }

    public function testFirstCalculationAlwaysUpdates(): void
    {
        $this->assertTrue(PriceDamper::shouldUpdate(null, null, '100.00', null, 1000.0));
    }

    public function testIdenticalPricesAreIdempotentEvenWithZeroStep(): void
    {
        $this->assertFalse(PriceDamper::shouldUpdate('100.00', null, '100.00', null, 0.0));
    }

    public function testBuyOfferAppearingOrDisappearingUpdates(): void
    {
        $this->assertTrue(PriceDamper::shouldUpdate('100.00', null, '100.00', '1.00', 1000.0));
        $this->assertTrue(PriceDamper::shouldUpdate('100.00', '1.00', '100.00', null, 1000.0));
    }
}
