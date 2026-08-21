<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Buyout\BuyoutRequestValidator;
use PHPUnit\Framework\TestCase;

final class BuyoutRequestValidatorTest extends TestCase
{
    public function testHoneypotRejects(): void
    {
        $out = BuyoutRequestValidator::validate([
            'website' => 'http://spam',
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'count' => 1,
        ]);
        $this->assertFalse($out['ok']);
        $this->assertSame(['honeypot'], $out['errors']);
    }

    public function testInvalidEmail(): void
    {
        $out = BuyoutRequestValidator::validate([
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'email' => 'not-an-email',
            'count' => 1,
        ]);
        $this->assertFalse($out['ok']);
        $this->assertContains('email', $out['errors']);
    }

    public function testEmptyEmailIsOptional(): void
    {
        $out = BuyoutRequestValidator::validate([
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'email' => '',
            'count' => 1,
        ]);
        $this->assertTrue($out['ok']);
        $this->assertSame('', $out['data']['email']);
    }

    public function testCountBounds(): void
    {
        $low = BuyoutRequestValidator::validate([
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'count' => 0,
        ]);
        $this->assertFalse($low['ok']);
        $this->assertContains('count', $low['errors']);

        $high = BuyoutRequestValidator::validate([
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'count' => 1001,
        ]);
        $this->assertFalse($high['ok']);
        $this->assertContains('count', $high['errors']);

        $ok = BuyoutRequestValidator::validate([
            'name' => 'Ivan',
            'phone' => '+79991234567',
            'count' => 1000,
        ]);
        $this->assertTrue($ok['ok']);
        $this->assertSame(1000, $ok['data']['count']);
    }

    public function testMissingNameOrPhone(): void
    {
        $out = BuyoutRequestValidator::validate([
            'name' => '',
            'phone' => '',
            'count' => 1,
        ]);
        $this->assertFalse($out['ok']);
        $this->assertContains('name', $out['errors']);
        $this->assertContains('phone', $out['errors']);
    }
}
