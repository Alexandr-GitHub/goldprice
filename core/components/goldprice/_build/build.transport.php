<?php
/**
 * Builds the goldprice transport package.
 * Requires _build/build.config.php, run on PHP 7.4:
 *   php7.4 _build/build.transport.php
 */

set_time_limit(0);
$tstart = microtime(true);

define('PKG_NAME', 'goldprice');
define('PKG_VERSION', '1.0.0');
define('PKG_RELEASE', 'pl');

$root = dirname(__DIR__) . '/';
$sources = array(
    'build' => __DIR__ . '/',
    'data' => __DIR__ . '/data/',
    'resolvers' => __DIR__ . '/resolvers/',
);

require_once $sources['build'] . 'build.config.php';
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget(defined('XPDO_CLI_MODE') && XPDO_CLI_MODE ? 'ECHO' : 'HTML');

$modx->loadClass('transport.modPackageBuilder', '', false, true);
$builder = new modPackageBuilder($modx);
$builder->createPackage(PKG_NAME, PKG_VERSION, PKG_RELEASE);
$builder->registerNamespace(
    PKG_NAME,
    false,
    true,
    '{core_path}components/' . PKG_NAME . '/',
    '{assets_path}components/' . PKG_NAME . '/'
);

$category = $modx->newObject('modCategory');
$category->set('id', 1);
$category->set('category', PKG_NAME);

$vehicle = $builder->createVehicle($category, array(
    xPDOTransport::UNIQUE_KEY => 'category',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
));
$vehicle->resolve('php', array('source' => $sources['resolvers'] . 'resolve.tables.php'));
$builder->putVehicle($vehicle);
$modx->log(modX::LOG_LEVEL_INFO, 'Category vehicle packed.');

$settings = include $sources['data'] . 'transport.settings.php';
foreach ($settings as $row) {
    $setting = $modx->newObject('modSystemSetting');
    $setting->fromArray($row, '', true, true);
    $builder->putVehicle($builder->createVehicle($setting, array(
        xPDOTransport::UNIQUE_KEY => 'key',
        xPDOTransport::PRESERVE_KEYS => true,
        xPDOTransport::UPDATE_OBJECT => false,
    )));
}
$modx->log(modX::LOG_LEVEL_INFO, 'Packed ' . count($settings) . ' system settings.');

$plugin = $modx->newObject('modPlugin');
$plugin->fromArray(array(
    'name' => 'goldprice',
    'description' => 'GoldPrice product pricing and storefront integration.',
    'plugincode' => preg_replace(
        '/^\s*<\?php\s*/',
        '',
        file_get_contents($root . 'elements/plugins/plugin.goldprice.php'),
        1
    ),
    'static' => 0,
), '', true, true);

foreach (array(
    'OnDocFormPrerender' => 20,
    'OnBeforeDocFormSave' => 20,
    'OnDocFormSave' => 20,
    'msOnGetProductPrice' => 100,
    'msOnBeforeAddToCart' => 0,
    'msOnAddToCart' => 0,
    'OnHandleRequest' => 0,
) as $eventName => $priority) {
    $event = $modx->newObject('modPluginEvent');
    $event->fromArray(array(
        'event' => $eventName,
        'priority' => $priority,
        'propertyset' => 0,
    ), '', true, true);
    $plugin->addMany($event);
}

$builder->putVehicle($builder->createVehicle($plugin, array(
    xPDOTransport::UNIQUE_KEY => 'name',
    xPDOTransport::PRESERVE_KEYS => false,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::RELATED_OBJECTS => true,
    xPDOTransport::RELATED_OBJECT_ATTRIBUTES => array(
        'PluginEvents' => array(
            xPDOTransport::PRESERVE_KEYS => true,
            xPDOTransport::UPDATE_OBJECT => true,
            xPDOTransport::UNIQUE_KEY => array('pluginid', 'event'),
        ),
    ),
)));
$modx->log(modX::LOG_LEVEL_INFO, 'Plugin and events packed.');

foreach (array('gpPrice', 'gpBuyoutForm', 'gpQuotes') as $snippetName) {
    $snippet = $modx->newObject('modSnippet');
    $snippet->fromArray(array(
        'name' => $snippetName,
        'description' => 'GoldPrice storefront snippet.',
        'snippet' => preg_replace(
            '/^\s*<\?php\s*/',
            '',
            file_get_contents($root . 'elements/snippets/' . $snippetName . '.php'),
            1
        ),
        'cacheable' => 0,
        'static' => 0,
    ), '', true, true);
    $builder->putVehicle($builder->createVehicle($snippet, array(
        xPDOTransport::UNIQUE_KEY => 'name',
        xPDOTransport::PRESERVE_KEYS => false,
        xPDOTransport::UPDATE_OBJECT => true,
    )));
}
$modx->log(modX::LOG_LEVEL_INFO, 'Storefront snippets packed.');

$menu = $modx->newObject('modMenu');
$menu->fromArray(array(
    'text' => 'goldprice.menu',
    'parent' => 'components',
    'description' => 'goldprice.menu_desc',
    'icon' => '',
    'menuindex' => 0,
    'params' => '',
    'handler' => '',
    'permissions' => 'settings',
    'namespace' => 'goldprice',
    'action' => 'home',
), '', true, true);
$builder->putVehicle($builder->createVehicle($menu, array(
    xPDOTransport::PRESERVE_KEYS => true,
    xPDOTransport::UPDATE_OBJECT => true,
    xPDOTransport::UNIQUE_KEY => 'text',
)));
$modx->log(modX::LOG_LEVEL_INFO, 'Menu item packed.');

// Whitelist keeps _build, tests, vendor and composer files out of the package.
foreach (array('model', 'src', 'lexicon', 'elements', 'cron', 'processors', 'controllers') as $dir) {
    if (!is_dir($root . $dir)) {
        continue;
    }
    $builder->package->put(
        array(
            'source' => $root . $dir,
            'target' => "return MODX_CORE_PATH . 'components/" . PKG_NAME . "/';",
        ),
        array('vehicle_class' => 'xPDOFileVehicle')
    );
}

$builder->package->put(
    array(
        'source' => $root . 'goldprice.class.php',
        'target' => "return MODX_CORE_PATH . 'components/" . PKG_NAME . "/';",
    ),
    array('vehicle_class' => 'xPDOFileVehicle')
);
$builder->package->put(
    array(
        'source' => $root . 'index.class.php',
        'target' => "return MODX_CORE_PATH . 'components/" . PKG_NAME . "/';",
    ),
    array('vehicle_class' => 'xPDOFileVehicle')
);

$assetsPath = MODX_ASSETS_PATH . 'components/' . PKG_NAME . '/';
if (is_dir($assetsPath)) {
    $builder->package->put(
        array(
            'source' => $assetsPath,
            'target' => "return MODX_ASSETS_PATH . 'components/';",
        ),
        array('vehicle_class' => 'xPDOFileVehicle')
    );
}
$modx->log(modX::LOG_LEVEL_INFO, 'File vehicles packed.');

$builder->setPackageAttributes(array(
    'license' => file_exists($sources['build'] . 'docs/license.txt')
        ? file_get_contents($sources['build'] . 'docs/license.txt')
        : 'MIT',
    'readme' => file_exists($sources['build'] . 'docs/readme.txt')
        ? file_get_contents($sources['build'] . 'docs/readme.txt')
        : 'GoldPrice — dynamic gold pricing for MODX.',
));

$builder->pack();

$modx->log(
    modX::LOG_LEVEL_INFO,
    'Package built in ' . sprintf('%2.4f s', microtime(true) - $tstart) . ': '
    . PKG_NAME . '-' . PKG_VERSION . '-' . PKG_RELEASE . '.transport.zip'
);
