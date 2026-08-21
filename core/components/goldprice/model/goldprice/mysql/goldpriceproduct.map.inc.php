<?php
$xpdo_meta_map['GoldPriceProduct']= array (
  'package' => 'goldprice',
  'version' => '1.1',
  'table' => 'goldprice_product',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'product_id' => NULL,
    'weight' => 0.0,
    'metal' => '',
    'coin_type' => '',
    'group_id' => NULL,
    'use_custom' => 0,
    'custom_pct' => 0.0,
    'custom_buy_pct' => 0.0,
    'custom_fix' => 0.0,
    'custom_buy_fix' => 0.0,
    'ignore_market' => 0,
    'fixed_price' => 0.0,
    'buyout_price' => 0.0,
  ),
  'fieldMeta' => 
  array (
    'product_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
    ),
    'weight' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '10,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'metal' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'coin_type' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'group_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => true,
    ),
    'use_custom' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'attributes' => 'unsigned',
      'phptype' => 'boolean',
      'null' => false,
      'default' => 0,
    ),
    'custom_pct' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'custom_buy_pct' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'custom_fix' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'custom_buy_fix' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'ignore_market' =>
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'attributes' => 'unsigned',
      'phptype' => 'boolean',
      'null' => false,
      'default' => 0,
    ),
    'fixed_price' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'buyout_price' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
  ),
  'indexes' => 
  array (
    'product_id' => 
    array (
      'alias' => 'product_id',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'product_id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
  'aggregates' => 
  array (
    'Group' => 
    array (
      'class' => 'GoldPriceGroup',
      'local' => 'group_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
    ),
  ),
);
