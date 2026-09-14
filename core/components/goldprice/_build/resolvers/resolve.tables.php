<?php
/**
 * Tables + seed resolver (category / menu). Migration runs first via raw SQL.
 *
 * @var xPDOTransport $transport
 * @var array $options
 * @var modX $modx
 */

if (!function_exists('goldpriceMigrateRawColumns')) {
    /**
     * @param modX $modx
     */
    function goldpriceMigrateRawColumns($modx)
    {
        $prefix = isset($modx->config['table_prefix']) ? (string) $modx->config['table_prefix'] : '';
        $modx->exec('SET SESSION lock_wait_timeout = 15');

        $migrations = array(
            array(
                'table' => $prefix . 'goldprice_group',
                'column' => 'parent_id',
                'definition' => '`parent_id` int(10) unsigned NULL DEFAULT NULL',
                'index' => 'parent_id',
            ),
            array(
                'table' => $prefix . 'goldprice_group',
                'column' => 'deleted_at',
                'definition' => '`deleted_at` datetime NULL DEFAULT NULL',
                'index' => null,
            ),
            array(
                'table' => $prefix . 'goldprice_product',
                'column' => 'custom_buy_fix',
                'definition' => '`custom_buy_fix` decimal(12,2) NOT NULL DEFAULT 0',
                'index' => null,
            ),
            array(
                'table' => $prefix . 'goldprice_group',
                'column' => 'add_to_parent',
                'definition' => '`add_to_parent` tinyint(1) unsigned NOT NULL DEFAULT 0',
                'index' => null,
                'after_add' => 'UPDATE `' . str_replace('`', '``', $prefix . 'goldprice_group')
                    . '` SET `add_to_parent` = 1 WHERE `parent_id` IS NOT NULL',
            ),
        );

        foreach ($migrations as $m) {
            $table = '`' . str_replace('`', '``', $m['table']) . '`';
            $column = $m['column'];
            $stmt = $modx->query('SHOW TABLES LIKE ' . $modx->quote($m['table']));
            if (!$stmt || !$stmt->fetch(PDO::FETCH_NUM)) {
                $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] migrate: table ' . $m['table'] . ' missing — skip');
                continue;
            }
            $stmt = $modx->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $modx->quote($column));
            if ($stmt === false) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] migrate: SHOW COLUMNS failed for ' . $m['table'] . '.' . $column);
                continue;
            }
            if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] migrate: ' . $m['table'] . '.' . $column . ' ok');
                continue;
            }
            $sql = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $m['definition'];
            if ($modx->exec($sql) === false) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] migrate: ALTER failed: ' . $sql);
                continue;
            }
            $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] migrate: added ' . $m['table'] . '.' . $column);

            if (!empty($m['after_add'])) {
                if ($modx->exec($m['after_add']) === false) {
                    $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] migrate: after_add failed: ' . $m['after_add']);
                } else {
                    $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] migrate: after_add ok for ' . $m['table'] . '.' . $column);
                }
            }

            if (!empty($m['index'])) {
                $hasIdx = false;
                $idx = $modx->query('SHOW INDEX FROM ' . $table);
                if ($idx) {
                    while ($row = $idx->fetch(PDO::FETCH_ASSOC)) {
                        if (isset($row['Key_name']) && $row['Key_name'] === $m['index']) {
                            $hasIdx = true;
                            break;
                        }
                    }
                }
                if (!$hasIdx) {
                    $sqlIdx = 'ALTER TABLE ' . $table . ' ADD KEY `' . $m['index'] . '` (`' . $column . '`)';
                    if ($modx->exec($sqlIdx) === false) {
                        $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] migrate: ADD KEY failed: ' . $sqlIdx);
                    } else {
                        $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] migrate: added key ' . $m['table'] . '.' . $m['index']);
                    }
                }
            }
        }
    }
}

if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $action = isset($options[xPDOTransport::PACKAGE_ACTION]) ? $options[xPDOTransport::PACKAGE_ACTION] : null;

    if ($action === xPDOTransport::ACTION_INSTALL || $action === xPDOTransport::ACTION_UPGRADE) {
        $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] Tables resolver start');
        goldpriceMigrateRawColumns($modx);

        $corePath = $modx->getOption('core_path') . 'components/goldprice/';
        $modelPath = $corePath . 'model/';

        if (!$modx->addPackage('goldprice', $modelPath, $modx->config['table_prefix'])) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to add xPDO package in resolver');
        } else {
            $classes = array(
                'GoldPriceGroup',
                'GoldPriceProduct',
                'GoldPriceQuote',
                'GoldPricePrice',
                'GoldPriceState',
                'GoldPriceLog',
                'GoldPriceRequest',
                'GoldPriceRecipient',
            );
            $manager = $modx->getManager();
            foreach ($classes as $class) {
                // false = table already exists — expected on upgrade
                $manager->createObjectContainer($class);
            }

            // Seed weight groups only when empty
            if ($modx->getCount('GoldPriceGroup') === 0) {
                $groups = array(
                    array(
                        'id' => 1,
                        'weight' => 31.10,
                        'title' => '1 Унция',
                        'sale_markup' => 5,
                        'sale_fix' => 500,
                        'buy_discount' => 5,
                        'buy_fix' => 1000,
                        'price_step' => 1000,
                        'stoploss' => 5,
                        'min_margin' => 0,
                    ),
                    array(
                        'id' => 2,
                        'weight' => 15.55,
                        'title' => '1/2 Унции',
                        'sale_markup' => 8,
                        'sale_fix' => 300,
                        'buy_discount' => 8,
                        'buy_fix' => 500,
                        'price_step' => 500,
                        'stoploss' => 5,
                        'min_margin' => 0,
                    ),
                    array(
                        'id' => 3,
                        'weight' => 7.78,
                        'title' => '1/4 Унции',
                        'sale_markup' => 12,
                        'sale_fix' => 200,
                        'buy_discount' => 12,
                        'buy_fix' => 300,
                        'price_step' => 200,
                        'stoploss' => 5,
                        'min_margin' => 0,
                    ),
                    array(
                        'id' => 4,
                        'weight' => 3.11,
                        'title' => '1/10 Унции',
                        'sale_markup' => 18,
                        'sale_fix' => 100,
                        'buy_discount' => 18,
                        'buy_fix' => 200,
                        'price_step' => 100,
                        'stoploss' => 5,
                        'min_margin' => 0,
                    ),
                );
                foreach ($groups as $group) {
                    $object = $modx->newObject('GoldPriceGroup');
                    $object->fromArray($group);
                    if (!$object->save()) {
                        $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to seed group: ' . $group['title']);
                    }
                }
            }

            if ($modx->getCount('GoldPriceRecipient') === 0) {
                $recipient = $modx->newObject('GoldPriceRecipient');
                $recipient->fromArray(array(
                    'email' => 'alexandrwd@yandex.ru',
                    'name' => '',
                    'active' => 1,
                    'storm_on' => 1,
                    'storm_off' => 1,
                    'daily_limit' => 1,
                    'api_error' => 1,
                    'new_request' => 1,
                ));
                if (!$recipient->save()) {
                    $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to seed default recipient');
                }
            }
        }
        $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] Tables resolver done');
    }
}

return true;
