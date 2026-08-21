<?php

declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use GoldPrice\Mgr\CmpFormat;
use PHPUnit\Framework\TestCase;

final class CmpFormatTest extends TestCase
{
    public function testZeroBuyPriceMeansNotOffered(): void
    {
        $this->assertSame('выкуп не предлагается', CmpFormat::buyPriceDisplay(0, 'выкуп не предлагается'));
        $this->assertSame('выкуп не предлагается', CmpFormat::buyPriceDisplay(0.0, 'выкуп не предлагается'));
        $this->assertSame('выкуп не предлагается', CmpFormat::buyPriceDisplay('0.00', 'выкуп не предлагается'));
        $this->assertSame('12345.5', CmpFormat::buyPriceDisplay(12345.5, 'выкуп не предлагается'));
    }

    public function testCsvHasBomSemicolonAndQuotedCyrillic(): void
    {
        $csv = CmpFormat::csv(
            ['created_at', 'event', 'message'],
            [[
                'created_at' => '2026-08-20 01:00:00',
                'event' => 'setting_change',
                'message' => 'Изменена ставка НДС',
            ]]
        );

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv);
        $this->assertStringContainsString(';', $csv);
        $this->assertStringContainsString('Изменена ставка НДС', $csv);
        $this->assertStringContainsString("\r\n", $csv);
    }

    public function testSettingDiffsSkipUnchanged(): void
    {
        $diffs = CmpFormat::settingDiffs(
            ['goldprice.vat_pct' => '22', 'goldprice.storm_window' => '3600'],
            ['goldprice.vat_pct' => '20', 'goldprice.storm_window' => '3600']
        );

        $this->assertCount(1, $diffs);
        $this->assertSame('goldprice.vat_pct', $diffs[0]['key']);
        $this->assertSame('22', $diffs[0]['old']);
        $this->assertSame('20', $diffs[0]['new']);
    }

    public function testSettingDiffsMaskApiSid(): void
    {
        $diffs = CmpFormat::settingDiffs(
            ['goldprice.pf_sid' => 'old-sid-value', 'goldprice.vat_pct' => '22'],
            ['goldprice.pf_sid' => 'new-secret', 'goldprice.vat_pct' => '20']
        );

        $this->assertCount(2, $diffs);
        $this->assertSame('••••', $diffs[0]['old']);
        $this->assertSame('••••', $diffs[0]['new']);
        $this->assertSame('22', $diffs[1]['old']);
        $this->assertSame('20', $diffs[1]['new']);
    }

    public function testSanitizeNumberAcceptsComma(): void
    {
        $this->assertSame(1000.5, CmpFormat::sanitizeNumber('1 000,5'));
        $this->assertNull(CmpFormat::sanitizeNumber('abc'));
    }
}
