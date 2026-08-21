<?php
/**
 * Migrate product TV values into goldprice_product.
 *
 * Lives in _build/ — one-shot ops tool, not shipped in the transport package.
 *
 * Weight sources (in order): grr → h_weight. TV newWeight is ignored (range category).
 * Owned fields (always upserted): weight, metal, coin_type, group_id, buyout_price,
 * use_custom, custom_pct, custom_buy_pct, ignore_market.
 * Preserved on update: custom_fix, custom_buy_fix, fixed_price (mgr-only / not from these TVs).
 *
 * custom_buy_pct is always written as 0: the legacy purchaseRatio multiplied the manual
 * buyoutPrice TV, not the spot base, so applying it to spot would buy above market.
 * Buyout falls back to the group buy_discount / buy_fix.
 *
 * Usage (PHP 7.4 on server):
 *   php7.4 _build/migrate_tv_to_product.php --dry-run
 *   php7.4 _build/migrate_tv_to_product.php
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only\n");
    exit(1);
}

$dryRun = in_array('--dry-run', $argv, true);

$configCore = dirname(__DIR__, 4) . '/config.core.php';
if (!is_file($configCore)) {
    $alt = dirname(__DIR__, 3) . '/config.core.php';
    if (is_file($alt)) {
        $configCore = $alt;
    }
}
if (!is_file($configCore)) {
    fwrite(STDERR, "config.core.php not found (tried {$configCore})\n");
    exit(1);
}

define('MODX_API_MODE', true);
require_once $configCore;
require_once MODX_CORE_PATH . 'model/modx/modx.class.php';

$modx = new modX();
$modx->initialize('mgr');
$modx->setLogLevel(modX::LOG_LEVEL_INFO);
$modx->setLogTarget('ECHO');

$corePath = $modx->getOption('core_path') . 'components/goldprice/';
require_once $corePath . 'goldprice.class.php';
GoldPrice::registerAutoload($corePath);
/** @var GoldPrice $gp */
$gp = new GoldPrice($modx, ['core_path' => $corePath]);
if (!$gp->initialize()) {
    fwrite(STDERR, "Failed to initialize goldprice package\n");
    exit(1);
}

use GoldPrice\Domain\Product\TvValueParser;
use GoldPrice\Domain\Product\WeightGroupMatcher;
use GoldPrice\Domain\Product\RatioMarkup;

$tvNames = ['grr', 'h_weight', 'metal', 'coinType', 'buyoutPrice', 'salesRatio', 'purchaseRatio'];
$tvIds = [];
foreach ($tvNames as $name) {
    $tv = $modx->getObject('modTemplateVar', ['name' => $name]);
    if (!$tv) {
        fwrite(STDERR, "TV not found: {$name}\n");
        exit(1);
    }
    $tvIds[$name] = (int) $tv->get('id');
}

$tolerance = (float) $modx->getOption('goldprice.weight_tolerance', null, 2);
$groups = [];
foreach ($gp->getGroupsList() as $g) {
    $groups[(int) $g['id']] = (float) $g['weight'];
}

$q = $modx->newQuery('modResource');
$q->where([
    'class_key' => 'msProduct',
    'deleted' => 0,
]);
$q->select($modx->getSelectColumns('modResource', 'modResource', '', ['id', 'pagetitle']));
$products = $modx->getIterator('modResource', $q);

$stats = [
    'products' => 0,
    'would_insert' => 0,
    'would_update' => 0,
    'written' => 0,
    'with_weight' => [],
    'without_weight' => [],
    'with_group' => [],
    'without_group' => [],
    'weight_from_grr' => [],
    'weight_from_h_weight' => [],
    'unrecognized_weight' => [],
    'unrecognized_metal' => [],
    'unrecognized_coin' => [],
    'unrecognized_buyout' => [],
    'ignore_market' => [],
    'per_product' => [],
];

foreach ($products as $product) {
    $stats['products']++;
    $pid = (int) $product->get('id');
    $title = (string) $product->get('pagetitle');

    $raw = [];
    foreach ($tvIds as $name => $tid) {
        $raw[$name] = '';
        $tvr = $modx->getObject('modTemplateVarResource', [
            'tmplvarid' => $tid,
            'contentid' => $pid,
        ]);
        if ($tvr) {
            $raw[$name] = (string) $tvr->get('value');
        }
    }

    $resolved = TvValueParser::resolveWeight($raw['grr'], $raw['h_weight']);
    $weight = $resolved['weight'];
    $weightSource = $resolved['source'];

    if ($resolved['status'] === 'ok') {
        $stats['with_weight'][] = $pid;
        if ($weightSource === 'grr') {
            $stats['weight_from_grr'][] = $pid;
        } elseif ($weightSource === 'h_weight') {
            $stats['weight_from_h_weight'][] = $pid;
        }
    } elseif ($resolved['status'] === 'empty') {
        $stats['without_weight'][] = "{$pid}\t{$title}";
    } else {
        $stats['unrecognized_weight'][] = "{$pid}\t{$title}\tsource={$weightSource}\traw="
            . ($weightSource === 'h_weight' ? $raw['h_weight'] : $raw['grr']);
        $stats['without_weight'][] = "{$pid}\t{$title}";
    }

    $metal = TvValueParser::parseMetal($raw['metal']);
    $ignoreMarket = false;
    if ($raw['metal'] !== '' && $metal === null) {
        $stats['unrecognized_metal'][] = "{$pid}\t{$title}\t{$raw['metal']}";
        $ignoreMarket = true;
        $stats['ignore_market'][] = "{$pid}\t{$title}\t{$raw['metal']}";
        $metal = '';
    } elseif ($metal === null) {
        $metal = '';
    }

    $coin = TvValueParser::parseCoinType($raw['coinType']);
    if ($raw['coinType'] !== '' && $raw['coinType'] !== '[]' && $coin === null) {
        $stats['unrecognized_coin'][] = "{$pid}\t{$title}\t{$raw['coinType']}";
        $coin = '';
    }
    if ($coin === null) {
        $coin = '';
    }

    $buyout = TvValueParser::parseBuyoutPrice($raw['buyoutPrice']);
    if ($raw['buyoutPrice'] !== '' && $buyout === null) {
        $stats['unrecognized_buyout'][] = "{$pid}\t{$title}\t{$raw['buyoutPrice']}";
    }

    $groupId = null;
    if ($weight !== null) {
        $groupId = WeightGroupMatcher::match($weight, $groups, $tolerance);
        if ($groupId === null) {
            $stats['without_group'][] = "{$pid}\t{$title}\tweight={$weight}";
        } else {
            $stats['with_group'][] = $pid;
        }
    } else {
        $stats['without_group'][] = "{$pid}\t{$title}\tweight=(none)";
    }

    $markup = RatioMarkup::fromSalesPurchase($raw['salesRatio'], $raw['purchaseRatio']);

    $row = [
        'weight' => $weight !== null ? $weight : 0.0,
        'metal' => $metal,
        'coin_type' => $coin,
        'group_id' => $groupId,
        'use_custom' => $markup['use_custom'],
        'custom_pct' => $markup['custom_pct'],
        'custom_buy_pct' => $markup['custom_buy_pct'],
        'custom_fix' => 0.0,
        'custom_buy_fix' => 0.0,
        'ignore_market' => $ignoreMarket,
        'fixed_price' => 0.0,
        'buyout_price' => $buyout !== null ? $buyout : 0.0,
    ];

    $stats['per_product'][] = sprintf(
        "%d\t%s\tweight=%s\tsource=%s\tgroup=%s\tmetal=%s\tignore_market=%d\tcustom_pct=%s"
            . "\tcustom_buy_pct=0\tpurchaseRatio=%s(ignored)",
        $pid,
        $title,
        $weight !== null ? (string) $weight : '(none)',
        $weightSource !== null ? $weightSource : '(none)',
        $groupId !== null ? (string) $groupId : '(none)',
        $metal !== '' ? $metal : '(none)',
        $ignoreMarket ? 1 : 0,
        (string) $markup['custom_pct'],
        $raw['purchaseRatio'] !== '' ? $raw['purchaseRatio'] : '(none)'
    );

    $existing = $modx->getObject('GoldPriceProduct', ['product_id' => $pid]);
    if ($existing) {
        // Preserve mgr-only fields not owned by this TV migration.
        $row['custom_fix'] = (float) $existing->get('custom_fix');
        $row['custom_buy_fix'] = (float) $existing->get('custom_buy_fix');
        $row['fixed_price'] = (float) $existing->get('fixed_price');
        $stats['would_update']++;
    } else {
        $stats['would_insert']++;
    }

    if ($dryRun) {
        continue;
    }

    if ($gp->saveProductData($pid, $row)) {
        $stats['written']++;
    }
}

echo "=== goldprice TV → product migration ===\n";
echo 'mode: ' . ($dryRun ? 'DRY-RUN' : 'WRITE') . "\n";
echo "products: {$stats['products']}\n";
echo "insert: {$stats['would_insert']}\n";
echo "update: {$stats['would_update']}\n";
if (!$dryRun) {
    echo "written: {$stats['written']}\n";
}
echo 'with_weight: ' . count($stats['with_weight']) . ' [' . implode(',', $stats['with_weight']) . "]\n";
echo 'without_weight: ' . count($stats['without_weight']) . "\n";
echo 'weight_from_grr: ' . count($stats['weight_from_grr']) . ' [' . implode(',', $stats['weight_from_grr']) . "]\n";
echo 'weight_from_h_weight: ' . count($stats['weight_from_h_weight']) . ' [' . implode(',', $stats['weight_from_h_weight']) . "]\n";
echo 'with_group: ' . count($stats['with_group']) . ' [' . implode(',', $stats['with_group']) . "]\n";
echo 'without_group: ' . count($stats['without_group']) . "\n";
echo 'unrecognized_weight: ' . count($stats['unrecognized_weight']) . "\n";
echo 'unrecognized_metal: ' . count($stats['unrecognized_metal']) . "\n";
echo 'unrecognized_coin: ' . count($stats['unrecognized_coin']) . "\n";
echo 'unrecognized_buyout: ' . count($stats['unrecognized_buyout']) . "\n";
echo 'ignore_market: ' . count($stats['ignore_market']) . "\n";

$sections = [
    'without_weight' => $stats['without_weight'],
    'without_group' => $stats['without_group'],
    'unrecognized_weight' => $stats['unrecognized_weight'],
    'unrecognized_metal' => $stats['unrecognized_metal'],
    'unrecognized_coin' => $stats['unrecognized_coin'],
    'unrecognized_buyout' => $stats['unrecognized_buyout'],
    'ignore_market' => $stats['ignore_market'],
    'per_product' => $stats['per_product'],
];
foreach ($sections as $label => $lines) {
    if (!$lines) {
        continue;
    }
    echo "--- {$label} ---\n";
    foreach ($lines as $line) {
        echo $line . "\n";
    }
}

exit(0);
