<?php
/**
 * @var xPDOTransport $transport
 * @var array $options
 * @var modX $modx
 */
if ($transport->xpdo) {
    $modx =& $transport->xpdo;
    $action = isset($options[xPDOTransport::PACKAGE_ACTION]) ? $options[xPDOTransport::PACKAGE_ACTION] : null;

    if ($action === xPDOTransport::ACTION_INSTALL || $action === xPDOTransport::ACTION_UPGRADE) {
        $corePath = $modx->getOption('core_path') . 'components/goldprice/';
        $modelPath = $corePath . 'model/';

        if (!$modx->addPackage('goldprice', $modelPath, $modx->config['table_prefix'])) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to add xPDO package in resolver');
        }

        $classes = [
            'GoldPriceGroup',
            'GoldPriceProduct',
            'GoldPriceQuote',
            'GoldPricePrice',
            'GoldPriceState',
            'GoldPriceLog',
            'GoldPriceRequest',
            'GoldPriceRecipient',
        ];

        $manager = $modx->getManager();
        foreach ($classes as $class) {
            if (!$manager->createObjectContainer($class)) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Could not create table for class: ' . $class);
            }
        }

        // createObjectContainer never ALTERs an existing table — migrations must be explicit.
        // Idempotent: safe on every install/upgrade. No AFTER clause (fragile if column order differs).
        goldpriceEnsureColumn(
            $modx,
            'GoldPriceGroup',
            'parent_id',
            '`parent_id` int(10) unsigned NULL DEFAULT NULL',
            'parent_id'
        );
        goldpriceEnsureColumn(
            $modx,
            'GoldPriceProduct',
            'custom_buy_fix',
            '`custom_buy_fix` decimal(12,2) NOT NULL DEFAULT 0'
        );

        // Seed weight groups from ТЗ п.5.1 only when table is empty (idempotent).
        // Ids are explicit: goldprice_product.group_id references them.
        // min_margin is absent in ТЗ п.5.1 — kept at 0 until the owner sets it.
        if ($modx->getCount('GoldPriceGroup') === 0) {
            $groups = [
                [
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
                ],
                [
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
                ],
                [
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
                ],
                [
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
                ],
            ];

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
            $recipient->fromArray([
                'email' => 'alexandrwd@yandex.ru',
                'name' => '',
                'active' => 1,
                'storm_on' => 1,
                'storm_off' => 1,
                'daily_limit' => 1,
                'api_error' => 1,
                'new_request' => 1,
            ]);
            if (!$recipient->save()) {
                $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to seed default recipient');
            }
        }
    }
}

/**
 * Add a missing column (and optional index) to an existing GoldPrice table.
 *
 * @param modX $modx
 * @param string $class xPDO class name
 * @param string $column
 * @param string $definition full column DDL without leading ADD COLUMN
 * @param string|null $indexName create KEY with this name if set
 */
if (!function_exists('goldpriceEnsureColumn')) {
    function goldpriceEnsureColumn($modx, $class, $column, $definition, $indexName = null)
    {
        $table = $modx->getTableName($class);
        if (!$table) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] ensureColumn: no table for ' . $class);
            return;
        }

        $stmt = $modx->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $modx->quote($column));
        if ($stmt === false) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] ensureColumn: SHOW COLUMNS failed for ' . $table . '.' . $column);
            return;
        }
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $sql = 'ALTER TABLE ' . $table . ' ADD COLUMN ' . $definition;
        if ($modx->exec($sql) === false) {
            $err = method_exists($modx, 'errorInfo') ? $modx->errorInfo() : array();
            $modx->log(
                modX::LOG_LEVEL_ERROR,
                '[goldprice] Failed ALTER ADD COLUMN ' . $table . '.' . $column . ': ' . json_encode($err) . ' SQL: ' . $sql
            );
            return;
        }
        $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] Added column ' . $table . '.' . $column);

        if ($indexName) {
            $hasIdx = false;
            $idx = $modx->query('SHOW INDEX FROM ' . $table);
            if ($idx) {
                while ($row = $idx->fetch(PDO::FETCH_ASSOC)) {
                    if (isset($row['Key_name']) && $row['Key_name'] === $indexName) {
                        $hasIdx = true;
                        break;
                    }
                }
            }
            if (!$hasIdx) {
                $sqlIdx = 'ALTER TABLE ' . $table . ' ADD KEY `' . $indexName . '` (`' . $column . '`)';
                if ($modx->exec($sqlIdx) === false) {
                    $err = method_exists($modx, 'errorInfo') ? $modx->errorInfo() : array();
                    $modx->log(
                        modX::LOG_LEVEL_ERROR,
                        '[goldprice] Failed ALTER ADD KEY ' . $table . '.' . $indexName . ': ' . json_encode($err)
                    );
                } else {
                    $modx->log(modX::LOG_LEVEL_INFO, '[goldprice] Added key ' . $table . '.' . $indexName);
                }
            }
        }

        $check = $modx->query('SHOW COLUMNS FROM ' . $table . ' LIKE ' . $modx->quote($column));
        if (!$check || !$check->fetch(PDO::FETCH_ASSOC)) {
            $modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Column still missing after ALTER: ' . $table . '.' . $column);
        }
    }
}

return true;
