<?php
declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class BuildDataTest extends TestCase
{
  private string $settingsPath;

  public function setUp(): void
  {
    $this->settingsPath = dirname(__DIR__, 2) . '/_build/data/transport.settings.php';
  }

  public function testTransportSettingsFileIsValid(): void
  {
    $this->assertFileExists($this->settingsPath);

    $source = file_get_contents($this->settingsPath);
    $this->assertNotFalse($source);
    $this->assertStringNotContainsString('$modx', $source);
    $this->assertStringNotContainsString('$modX', $source);

    $settings = include $this->settingsPath;
    $this->assertIsArray($settings);
    $this->assertNotEmpty($settings);

    $keys = [];
    foreach ($settings as $index => $setting) {
      $this->assertIsArray($setting, "Setting at index {$index} is not an array");
      $this->assertArrayHasKey('key', $setting);
      $this->assertArrayHasKey('value', $setting);
      $this->assertArrayHasKey('xtype', $setting);
      $this->assertArrayHasKey('namespace', $setting);
      $this->assertArrayHasKey('area', $setting);

      $this->assertStringStartsWith('goldprice.', $setting['key']);
      $this->assertSame('goldprice', $setting['namespace']);

      $keys[] = $setting['key'];
    }

    $this->assertSame(count($keys), count(array_unique($keys)), 'Duplicate setting keys found');
  }

  public function testVatSettingIsShippedAndTranslated(): void
  {
    $settings = include $this->settingsPath;

    $vat = null;
    foreach ($settings as $setting) {
      if ($setting['key'] === 'goldprice.vat_pct') {
        $vat = $setting;
      }
      $this->assertNotSame('goldprice.vat', $setting['key'], 'goldprice.vat is replaced by goldprice.vat_pct');
    }

    $this->assertNotNull($vat, 'goldprice.vat_pct setting is missing');
    $this->assertSame('22', (string) $vat['value']);
    $this->assertSame('goldprice.pricing', $vat['area']);

    foreach (['ru', 'en'] as $lang) {
      $_lang = [];
      require dirname(__DIR__, 2) . '/lexicon/' . $lang . '/default.inc.php';
      $this->assertArrayHasKey('setting_goldprice.vat_pct', $_lang, $lang);
      $this->assertArrayHasKey('setting_goldprice.vat_pct_desc', $_lang, $lang);
      $this->assertArrayNotHasKey('setting_goldprice.vat', $_lang, $lang);
    }
  }

  public function testStormSettingsAreShippedInSecondsAndTranslated(): void
  {
    $byKey = [];
    foreach (include $this->settingsPath as $setting) {
      $byKey[$setting['key']] = $setting;
    }

    foreach (['goldprice.storm_window', 'goldprice.storm_duration'] as $key) {
      $this->assertArrayHasKey($key, $byKey, $key . ' setting is missing');
      $this->assertSame('3600', (string) $byKey[$key]['value'], $key . ' is expressed in seconds');
      $this->assertSame('goldprice.storm', $byKey[$key]['area']);
    }

    $this->assertArrayHasKey('goldprice.cart_ttl', $byKey);
    $this->assertSame('3600', (string) $byKey['goldprice.cart_ttl']['value']);
    $this->assertSame('goldprice.pricing', $byKey['goldprice.cart_ttl']['area']);

    foreach (['ru', 'en'] as $lang) {
      $_lang = [];
      require dirname(__DIR__, 2) . '/lexicon/' . $lang . '/default.inc.php';
      foreach ([
        'setting_goldprice.storm_window',
        'setting_goldprice.storm_window_desc',
        'setting_goldprice.storm_duration',
        'setting_goldprice.storm_duration_desc',
        'goldprice.storm_sale_paused',
        'goldprice.storm_buy_paused',
      ] as $key) {
        $this->assertArrayHasKey($key, $_lang, $lang . ': ' . $key);
        $this->assertNotSame('', trim((string) $_lang[$key]), $lang . ': ' . $key);
      }
    }

    $_lang = [];
    require dirname(__DIR__, 2) . '/lexicon/ru/default.inc.php';
    $this->assertStringContainsString('секунд', $_lang['setting_goldprice.storm_window_desc']);
    $this->assertStringContainsString('секунд', $_lang['setting_goldprice.storm_duration_desc']);
    // ТЗ п.7.2 переписан под п.6.2: обвал останавливает продажу, рост — приём на выкуп
        $this->assertStringContainsString('продаж', $_lang['goldprice.storm_sale_paused']);
        $this->assertStringContainsString('выкуп', $_lang['goldprice.storm_buy_paused']);
        $this->assertArrayHasKey('setting_goldprice.cart_ttl', $_lang);
        $this->assertStringContainsString('секунд', $_lang['setting_goldprice.cart_ttl_desc']);
  }
}
