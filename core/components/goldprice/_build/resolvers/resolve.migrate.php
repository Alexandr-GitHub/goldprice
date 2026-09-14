<?php
/**
 * Early migration on modNamespace — runs before any file vehicle / preserved.zip.
 *
 * @var xPDOTransport $transport
 * @var array $options
 */
if (empty($transport->xpdo)) {
    return true;
}

/** @var modX $modx */
$modx =& $transport->xpdo;
$action = isset($options[xPDOTransport::PACKAGE_ACTION]) ? $options[xPDOTransport::PACKAGE_ACTION] : null;
if ($action !== xPDOTransport::ACTION_INSTALL && $action !== xPDOTransport::ACTION_UPGRADE) {
    return true;
}

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

$modx->log(modX::LOG_LEVEL_INFO, '[goldprice] Early migrate (namespace)');
goldpriceMigrateRawColumns($modx);

return true;
