<?php
/**
 * Cron / manual: fetch Profinance quotes, persist, sync ClientConfig.
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$path = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);

require_once $path . 'goldprice.class.php';
GoldPrice::registerAutoload($path);

$gp = new GoldPrice($modx);

$useFixture = !empty($scriptProperties['fixture']);
if ($useFixture) {
    $fixturePath = (string) $scriptProperties['fixture'];
    if ($fixturePath[0] !== '/') {
        $fixturePath = $path . $fixturePath;
    }
    $tickers = (string) $modx->getOption('goldprice.pf_tickers', null, 'gold;USDRUB');
    $expected = array_values(array_filter(array_map('trim', explode(';', $tickers))));
    $maxAge = (int) $modx->getOption('goldprice.quote_max_age', null, 900);
    $usdMaxAge = (int) $modx->getOption('goldprice.usd_max_age', null, 86400);
    $now = isset($scriptProperties['now']) ? (int) $scriptProperties['now'] : null;
    $provider = new \GoldPrice\Domain\Quote\FixtureQuoteProvider(
        $fixturePath,
        $expected !== [] ? $expected : ['gold', 'USDRUB'],
        $maxAge,
        $now,
        $usdMaxAge
    );
} else {
    $provider = new \GoldPrice\Domain\Quote\ProfinanceQuoteProvider([
        'url' => (string) $modx->getOption('goldprice.pf_url', null, ''),
        'sid' => (string) $modx->getOption('goldprice.pf_sid', null, ''),
        'tickers' => (string) $modx->getOption('goldprice.pf_tickers', null, 'gold;USDRUB'),
        'bind_ip' => (string) $modx->getOption('goldprice.pf_bind_ip', null, ''),
        'timeout' => (int) $modx->getOption('goldprice.pf_timeout', null, 10),
        'max_age' => (int) $modx->getOption('goldprice.quote_max_age', null, 900),
        'usd_max_age' => (int) $modx->getOption('goldprice.usd_max_age', null, 86400),
    ]);
}

$updater = new \GoldPrice\Service\QuoteUpdater($modx, $gp, $provider);
$result = $updater->update();

if (!empty($scriptProperties['returnJson'])) {
    $payload = [
        'ok' => $result['ok'],
        'stale' => $result['stale'],
        'skipped' => $result['skipped'],
        'message' => $result['message'],
        'xau_usd' => null,
        'usd_rub' => null,
        'gold' => null,
    ];
    if (isset($result['quote'])) {
        /** @var \GoldPrice\Domain\Quote\Quote $q */
        $q = $result['quote'];
        // Strings avoid serialize_precision float noise on this host
        $payload['xau_usd'] = number_format($q->getXauUsd(), 4, '.', '');
        $payload['usd_rub'] = number_format($q->getUsdRub(), 4, '.', '');
        $payload['gold'] = number_format($q->goldRubPerGram(), 2, '.', '');
    }

    return json_encode($payload);
}

if ($result['ok']) {
    return 'ok';
}
if ($result['skipped']) {
    return 'skipped';
}
if ($result['stale']) {
    return 'stale';
}

return 'error';
