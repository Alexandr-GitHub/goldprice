<?php
/**
 * Regenerates xPDO model classes and maps from the schema.
 * Requires a working MODX installation, run on PHP 7.4:
 *   php7.4 _build/build.schema.php
 */

$componentPath = dirname(__DIR__);
$basePath = dirname($componentPath, 3) . '/';

require_once $basePath . 'config.core.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$manager = $modx->getManager();
$generator = $manager->getGenerator();
$generator->parseSchema(
    $componentPath . '/model/schema/goldprice.mysql.schema.xml',
    $componentPath . '/model/'
);
