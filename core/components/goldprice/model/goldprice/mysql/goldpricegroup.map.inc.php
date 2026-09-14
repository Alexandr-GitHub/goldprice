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
    'parent_id' => null,
    'add_to_parent' => false,
    'deleted_at' => null,
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
    'parent_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => true,
    ),
    'add_to_parent' => 
    array (
      'dbtype' => 'tinyint',
      'precision' => '1',
      'attributes' => 'unsigned',
      'phptype' => 'boolean',
      'null' => false,
      'default' => 0,
    ),
    'deleted_at' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => true,
    ),
  ),
  'aggregates' => 
  array (
    'Parent' => 
    array (
      'class' => 'GoldPriceGroup',
      'local' => 'parent_id',
      'foreign' => 'id',
      'cardinality' => 'one',
      'owner' => 'foreign',
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
    'parent_id' => 
    array (
      'alias' => 'parent_id',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'parent_id' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
