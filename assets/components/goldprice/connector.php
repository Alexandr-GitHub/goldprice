<?php
/**
 * GoldPrice mgr connector. Requires an authenticated mgr admin.
 *
 * @var modX $modx
 */

require_once dirname(__FILE__, 4) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CONNECTORS_PATH . 'index.php';

$corePath = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);
require_once $corePath . 'goldprice.class.php';

$modx->goldprice = new GoldPrice($modx, ['core_path' => $corePath]);
$modx->goldprice->initialize();
$modx->lexicon->load('goldprice:default');

if (!$modx->goldprice->isMgrAdmin()) {
    header('Content-Type: application/json; charset=UTF-8');
    echo $modx->toJSON([
        'success' => false,
        'message' => $modx->lexicon('access_denied'),
    ]);
    exit;
}

$modx->request->handleRequest([
    'processors_path' => $corePath . 'processors/',
    'location' => '',
]);
