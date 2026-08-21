<?php
/**
 * GoldPrice public web action (no mgr auth).
 * Allowed: web/request/create
 */

header('Content-Type: application/json; charset=UTF-8');

define('MODX_API_MODE', true);
require_once dirname(__FILE__, 4) . '/config.core.php';
require_once MODX_CORE_PATH . 'config/' . MODX_CONFIG_KEY . '.inc.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('web');

$corePath = $modx->getOption(
    'goldprice.core_path',
    null,
    $modx->getOption('core_path') . 'components/goldprice/'
);
require_once $corePath . 'goldprice.class.php';

$modx->goldprice = new GoldPrice($modx, ['core_path' => $corePath]);
$modx->goldprice->initialize();
$modx->lexicon->load('goldprice:default');

$action = isset($_REQUEST['action']) ? (string) $_REQUEST['action'] : '';
if ($action !== 'web/request/create') {
    echo $modx->toJSON([
        'success' => false,
        'message' => $modx->lexicon('access_denied'),
    ]);
    exit;
}

$props = array_merge($_GET, $_POST);
unset($props['action']);

$response = $modx->runProcessor('web/request/create', $props, [
    'processors_path' => $corePath . 'processors/',
]);

if (!$response) {
    echo $modx->toJSON([
        'success' => false,
        'message' => $modx->lexicon('goldprice.err_save'),
    ]);
    exit;
}

$raw = $response->getResponse();
if (is_array($raw)) {
    echo $modx->toJSON($raw);
} else {
    echo (string) $raw;
}
