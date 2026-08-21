<?php
/**
 * Storefront: read pre-calculated prices from goldprice_price (no formulas).
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$productId = (int) ($scriptProperties['id'] ?? $scriptProperties['product'] ?? 0);
$path = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);
require_once $path . 'goldprice.class.php';
GoldPrice::registerAutoload($path);

$modx->lexicon->load('goldprice:default');
$maxAge = (int) $modx->getOption('goldprice.quote_max_age', null, 900);
$unavailable = 'Цену уточняйте';
$buyNotOffered = (string) $modx->lexicon('goldprice.buy_not_offered');
$salePaused = (string) $modx->lexicon('goldprice.storm_sale_paused');
$buyPaused = (string) $modx->lexicon('goldprice.storm_buy_paused');

if ($productId <= 0) {
    return json_encode(
        \GoldPrice\Domain\Storefront\StorefrontPricePresenter::present(
            null,
            0.0,
            time(),
            $maxAge,
            '',
            '',
            $unavailable,
            $buyNotOffered
        ),
        JSON_UNESCAPED_UNICODE
    );
}

$quote = \GoldPrice\Service\StorefrontPriceLoader::quote($modx, $path);
$usdRate = $quote ? $quote->getUsdRub() : 0.0;
$row = \GoldPrice\Service\StorefrontPriceLoader::row($modx, $path, $productId);
$quoteAt = \GoldPrice\Service\StorefrontPriceLoader::quoteAt($modx, $path);

$payload = \GoldPrice\Domain\Storefront\StorefrontPricePresenter::present(
    $row,
    $usdRate,
    time(),
    $maxAge,
    $salePaused,
    $buyPaused,
    $unavailable,
    $buyNotOffered,
    $quoteAt
);

return json_encode($payload, JSON_UNESCAPED_UNICODE);
