<?php
/**
 * GoldPrice plugin — product tab, storefront cart price.
 * Register msOnGetProductPrice at priority 100 (after msOptionsPrice).
 *
 * @var modX $modx
 * @var array $scriptProperties
 */

$corePath = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);
require_once $corePath . 'goldprice.class.php';
GoldPrice::registerAutoload($corePath);

/** @var GoldPrice $goldprice */
$goldprice = new GoldPrice($modx, ['core_path' => $corePath]);

switch ($modx->event->name) {
    case 'OnDocFormPrerender':
        $goldprice->onDocFormPrerender($scriptProperties);
        break;
    case 'OnBeforeDocFormSave':
        $goldprice->onBeforeDocFormSave($scriptProperties);
        break;
    case 'OnDocFormSave':
        $goldprice->onDocFormSave($scriptProperties);
        break;
    case 'msOnGetProductPrice':
        $goldprice->onGetProductPrice($scriptProperties);
        break;
    case 'msOnBeforeAddToCart':
        $goldprice->onBeforeAddToCart($scriptProperties);
        break;
    case 'msOnAddToCart':
        $goldprice->onAddToCart($scriptProperties);
        break;
    case 'OnHandleRequest':
        $goldprice->purgeExpiredCartItems();
        break;
}
