<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Domain\Product\TvValueParser;
use PHPUnit\Framework\TestCase;

final class TvValueParserTest extends TestCase
{
    public function testParseWeightAcceptsCommaAndTrash(): void
    {
        $this->assertSame(7.78, TvValueParser::parseWeight('7,78 г'));
        $this->assertSame(31.1, TvValueParser::parseWeight('31.1'));
        $this->assertSame(3.11, TvValueParser::parseWeight(' 3,1100 '));
        $this->assertSame(155.5, TvValueParser::parseWeight('155.5'));
    }

    public function testParseWeightAcceptsUnitSuffixes(): void
    {
        $this->assertSame(7.78, TvValueParser::parseWeight('7.78 гр'));
        $this->assertSame(31.1, TvValueParser::parseWeight('31.1 гр'));
        $this->assertSame(7.78, TvValueParser::parseWeight('7,78 грамм'));
        $this->assertSame(15.55, TvValueParser::parseWeight('15.55г'));
    }

    public function testParseWeightEmptyAndInvalid(): void
    {
        $this->assertNull(TvValueParser::parseWeight(''));
        $this->assertNull(TvValueParser::parseWeight(null));
        $this->assertNull(TvValueParser::parseWeight('нет'));
        $this->assertNull(TvValueParser::parseWeight('-1'));
        $this->assertNull(TvValueParser::parseWeight('от 15.55'));
    }

    public function testResolveWeightPrefersGrrThenHWeight(): void
    {
        $fromGrr = TvValueParser::resolveWeight('7.78', '99');
        $this->assertSame(7.78, $fromGrr['weight']);
        $this->assertSame('grr', $fromGrr['source']);
        $this->assertSame('ok', $fromGrr['status']);

        $fromH = TvValueParser::resolveWeight('', '7.78 гр');
        $this->assertSame(7.78, $fromH['weight']);
        $this->assertSame('h_weight', $fromH['source']);
        $this->assertSame('ok', $fromH['status']);

        $empty = TvValueParser::resolveWeight('', '');
        $this->assertNull($empty['weight']);
        $this->assertNull($empty['source']);
        $this->assertSame('empty', $empty['status']);

        $badGrr = TvValueParser::resolveWeight('нет', '7.78');
        $this->assertNull($badGrr['weight']);
        $this->assertSame('grr', $badGrr['source']);
        $this->assertSame('unrecognized', $badGrr['status']);
    }

    public function testParseMetalNormalizesKnown(): void
    {
        $this->assertSame('золото', TvValueParser::parseMetal(' Золото '));
        $this->assertSame('серебро', TvValueParser::parseMetal('СЕРЕБРО'));
    }

    public function testParseMetalUnknownReturnsNull(): void
    {
        $this->assertNull(TvValueParser::parseMetal('метал'));
        $this->assertNull(TvValueParser::parseMetal('платина'));
        $this->assertNull(TvValueParser::parseMetal(''));
    }

    public function testParseCoinTypeFromJsonAndPlain(): void
    {
        $this->assertSame('инвестиционные', TvValueParser::parseCoinType('["инвестиционные"]'));
        $this->assertSame('памятные', TvValueParser::parseCoinType('["памятные"]'));
        $this->assertSame('инвестиционные', TvValueParser::parseCoinType('Инвестиционные'));
        $this->assertSame('', TvValueParser::parseCoinType('[]'));
        $this->assertSame('', TvValueParser::parseCoinType(''));
        $this->assertNull(TvValueParser::parseCoinType('["неизвестно"]'));
    }

    public function testParseBuyoutPriceStripsSpaces(): void
    {
        $this->assertSame(150000.0, TvValueParser::parseBuyoutPrice('150000'));
        $this->assertSame(190.5, TvValueParser::parseBuyoutPrice('190.50'));
        $this->assertSame(1000.0, TvValueParser::parseBuyoutPrice('1 000'));
        $this->assertSame(1234.56, TvValueParser::parseBuyoutPrice('1 234,56'));
        $this->assertNull(TvValueParser::parseBuyoutPrice(''));
        $this->assertNull(TvValueParser::parseBuyoutPrice(null));
        $this->assertNull(TvValueParser::parseBuyoutPrice('abc'));
    }
}
