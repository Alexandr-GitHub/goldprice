<?php
/**
 * Storefront ticker: latest goldprice_quote as JSON (gold/gold2/usd/usd2 + gold_usd).
 *
 * @var modX $modx
 */

$path = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);
require_once $path . 'goldprice.class.php';
GoldPrice::registerAutoload($path);

$empty = [
    'gold' => '',
    'gold2' => '',
    'usd' => '',
    'usd2' => '',
    'gold_usd' => '',
    'gold_usd2' => '',
    'stale' => false,
    'quote_stale' => false,
    'server_ts' => time(),
];

$quote = \GoldPrice\Service\StorefrontPriceLoader::quote($modx, $path);
if ($quote === null) {
    return json_encode($empty, JSON_UNESCAPED_UNICODE);
}

$rates = $quote->storefrontRates();
$rates['gold_usd'] = number_format($quote->getXauUsd(), 2, '.', '');
$rates['gold_usd2'] = number_format($quote->getNetchangeGold(), 2, '.', '');
$now = time();
$maxAge = (int) $modx->getOption('goldprice.quote_max_age', null, 900);
$quoteAt = \GoldPrice\Service\StorefrontPriceLoader::quoteAt($modx, $path);
$stale = \GoldPrice\Domain\Storefront\PriceAvailability::isStale($quoteAt, $now, $maxAge);
$quoteStale = !$quote->isGoldFresh($maxAge, $now) || !$quote->isUsdFresh($maxAge, $now);
$rates['stale'] = (bool) $stale;
$rates['quote_stale'] = (bool) $quoteStale;
$rates['server_ts'] = $now;

return json_encode($rates, JSON_UNESCAPED_UNICODE);
