<?php
$xpdo_meta_map['GoldPriceGroup']= array (
  'package' => 'goldprice',
  'version' => '1.1',
  'table' => 'goldprice_group',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'weight' => 0.0,
    'title' => '',
    'sale_markup' => 0.0,
    'sale_fix' => 0.0,
    'buy_discount' => 0.0,
    'buy_fix' => 0.0,
    'price_step' => 0.0,
    'stoploss' => 0.0,
    'min_margin' => 0.0,
  ),
  'fieldMeta' => 
  array (
    'weight' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '10,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'title' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '64',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'sale_markup' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'sale_fix' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'buy_discount' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'buy_fix' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'price_step' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,2',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'stoploss' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'min_margin' => 
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
    'weight' => 
    array (
      'alias' => 'weight',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'weight' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
