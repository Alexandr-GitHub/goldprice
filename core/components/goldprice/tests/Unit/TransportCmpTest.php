<?php

declare(strict_types=1);

namespace GoldPrice\Tests\Unit;

use PHPUnit\Framework\TestCase;

final class TransportCmpTest extends TestCase
{
    public function testBuildPacksMenuProcessorsAndControllers(): void
    {
        $root = dirname(__DIR__, 2);
        $build = file_get_contents($root . '/_build/build.transport.php');
        $this->assertNotFalse($build);

        $this->assertStringContainsString('modMenu', $build);
        $this->assertStringContainsString("'action' => 'home'", $build);
        $this->assertStringContainsString("'namespace' => 'goldprice'", $build);
        $this->assertStringContainsString('goldprice.menu', $build);
        $this->assertStringContainsString("'processors'", $build);
        $this->assertStringContainsString("'controllers'", $build);
        $this->assertStringContainsString('index.class.php', $build);
        $this->assertStringContainsString("'gpPrice', 'gpBuyoutForm', 'gpQuotes'", $build);
    }

    public function testProcessorsReturnClassNames(): void
    {
        $root = dirname(__DIR__, 2) . '/processors/mgr';
        $this->assertDirectoryExists($root);

        $files = new \RegexIterator(
            new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            ),
            '#\.class\.php$#'
        );

        $checked = 0;
        foreach ($files as $file) {
            $source = file_get_contents($file->getPathname());
            $this->assertNotFalse($source);
            $this->assertMatchesRegularExpression(
                "/return\\s+'GoldPriceMgr\\w+Processor';/",
                $source,
                $file->getFilename() . ' must return the processor class name'
            );
            $this->assertStringContainsString("public \$permission = 'settings';", $source, $file->getFilename());
            ++$checked;
        }

        $this->assertGreaterThanOrEqual(10, $checked);
    }

    public function testSettingsUpdateUsesGetPropertiesNotIssetProperty(): void
    {
        $src = file_get_contents(dirname(__DIR__, 2) . '/processors/mgr/settings/update.class.php');
        $this->assertNotFalse($src);
        $this->assertStringNotContainsString('->issetProperty(', $src);
        $this->assertStringContainsString('array_key_exists', $src);
    }

    public function testProductTabDeclaresBuyoutMarkup(): void
    {
        $js = file_get_contents(dirname(__DIR__, 5) . '/assets/components/goldprice/js/mgr/product/product.tab.js');
        $this->assertNotFalse($js);
        $this->assertStringContainsString("name: 'goldprice[custom_buy_pct]'", $js);
        $this->assertStringContainsString("id: 'goldprice-custom-buy-pct'", $js);
        $this->assertStringContainsString('_(\'goldprice.field_custom_buy_pct\')', $js);
        $this->assertStringContainsString("name: 'goldprice[custom_buy_fix]'", $js);
        $this->assertStringContainsString("id: 'goldprice-custom-buy-fix'", $js);
        $this->assertStringContainsString('_(\'goldprice.field_custom_buy_fix\')', $js);
        $this->assertStringContainsString('autoScroll: true', $js);

        $php = file_get_contents(dirname(__DIR__, 2) . '/goldprice.class.php');
        $this->assertNotFalse($php);
        $this->assertStringContainsString("product.tab.js", $php);
        $this->assertStringContainsString("?v=", $php);

        $resolver = file_get_contents(dirname(__DIR__, 2) . '/_build/resolvers/resolve.tables.php');
        $this->assertNotFalse($resolver);
        $this->assertStringContainsString('custom_buy_fix', $resolver);
    }

    public function testStaleQuoteIsNotWrittenToModxErrorLog(): void
    {
        $updater = file_get_contents(dirname(__DIR__, 2) . '/src/Service/QuoteUpdater.php');
        $this->assertNotFalse($updater);
        $this->assertStringNotContainsString("'[goldprice] Stale quote:", $updater);

        $cron = file_get_contents(dirname(__DIR__, 2) . '/cron/recalculate.php');
        $this->assertNotFalse($cron);
        $this->assertStringContainsString("quote=paused (market closed)", $cron);
        $this->assertStringContainsString("['stale']", $cron);
    }

    public function testCartTtlIsHookedInPluginAndTransport(): void
    {
        $root = dirname(__DIR__, 2);
        $plugin = file_get_contents($root . '/elements/plugins/plugin.goldprice.php');
        $this->assertNotFalse($plugin);
        $this->assertStringContainsString("case 'msOnAddToCart':", $plugin);
        $this->assertStringContainsString("case 'OnHandleRequest':", $plugin);

        $build = file_get_contents($root . '/_build/build.transport.php');
        $this->assertNotFalse($build);
        $this->assertStringContainsString("'msOnAddToCart'", $build);
        $this->assertStringContainsString("'OnHandleRequest'", $build);

        $php = file_get_contents($root . '/goldprice.class.php');
        $this->assertNotFalse($php);
        $this->assertStringContainsString('purgeExpiredCartItems', $php);
        $this->assertStringContainsString("goldprice.cart_ttl", $php);
    }
}
