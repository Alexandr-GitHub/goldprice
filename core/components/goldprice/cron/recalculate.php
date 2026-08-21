<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit(1);
}

define('MODX_API_MODE', true);
$corePath = dirname(__DIR__, 3) . '/';
require_once $corePath . 'config/config.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');

$componentPath = (string) $modx->getOption(
    'goldprice.core_path',
    null,
    MODX_CORE_PATH . 'components/goldprice/'
);
require_once $componentPath . 'goldprice.class.php';
GoldPrice::registerAutoload($componentPath);

$lockPath = rtrim((string) $modx->getOption('core_path'), '/') . '/cache/goldprice/recalculate.lock';
$lockDir = dirname($lockPath);
if (!is_dir($lockDir) && !mkdir($lockDir, 0755, true) && !is_dir($lockDir)) {
    fwrite(STDERR, "Cannot create goldprice lock directory\n");
    exit(1);
}
$lock = fopen($lockPath, 'c+');
if ($lock === false) {
    fwrite(STDERR, "Cannot open goldprice lock file\n");
    exit(1);
}
if (!flock($lock, LOCK_EX | LOCK_NB)) {
    fclose($lock);
    exit(0);
}
ftruncate($lock, 0);
fwrite($lock, (string) getmypid());
fflush($lock);

try {
    $goldprice = new GoldPrice($modx);
    $provider = new \GoldPrice\Domain\Quote\ProfinanceQuoteProvider([
        'url' => (string) $modx->getOption('goldprice.pf_url', null, ''),
        'sid' => (string) $modx->getOption('goldprice.pf_sid', null, ''),
        'tickers' => (string) $modx->getOption('goldprice.pf_tickers', null, 'gold;USDRUB'),
        'bind_ip' => (string) $modx->getOption('goldprice.pf_bind_ip', null, ''),
        'timeout' => (int) $modx->getOption('goldprice.pf_timeout', null, 10),
        'max_age' => (int) $modx->getOption('goldprice.quote_max_age', null, 900),
        'usd_max_age' => (int) $modx->getOption('goldprice.usd_max_age', null, 86400),
    ]);
    $quote = (new \GoldPrice\Service\QuoteUpdater($modx, $goldprice, $provider))->update();
    if (!empty($quote['ok']) && !empty($quote['stale'])) {
        fwrite(STDOUT, "quote=paused (market closed)\n");
        exit(0);
    }
    if (!$quote['ok']) {
        fwrite(STDERR, 'quote: ' . $quote['message'] . PHP_EOL);
        exit(1);
    }

    $summary = (new \GoldPrice\Service\PriceRecalculator($modx, $goldprice))->recalculate();
    if (!$summary['ok']) {
        fwrite(STDERR, 'prices: ' . $summary['message'] . PHP_EOL);
        exit(1);
    }

    printf(
        "quote=ok calculated=%d updated=%d held=%d skipped=%d\n",
        $summary['calculated'],
        $summary['updated'],
        $summary['held'],
        $summary['skipped']
    );
    exit(0);
} catch (\Throwable $e) {
    $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Cron failed: ' . $e->getMessage());
    fwrite(STDERR, 'goldprice: ' . $e->getMessage() . PHP_EOL);
    exit(1);
} finally {
    flock($lock, LOCK_UN);
    fclose($lock);
}
