<?php
/**
 * Storefront ticker: latest goldprice_quote as JSON (gold/gold2/usd/usd2).
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
];

$quote = \GoldPrice\Service\StorefrontPriceLoader::quote($modx, $path);
if ($quote === null) {
    return json_encode($empty, JSON_UNESCAPED_UNICODE);
}

return json_encode($quote->storefrontRates(), JSON_UNESCAPED_UNICODE);
