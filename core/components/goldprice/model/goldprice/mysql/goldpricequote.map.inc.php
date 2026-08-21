<?php
$xpdo_meta_map['GoldPriceQuote']= array (
  'package' => 'goldprice',
  'version' => '1.1',
  'table' => 'goldprice_quote',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'created_at' => NULL,
    'xau_usd' => 0.0,
    'usd_rub' => 0.0,
    'bid' => 0.0,
    'ask' => 0.0,
    'netchange_pct' => 0.0,
    'source' => '',
    'raw' => NULL,
  ),
  'fieldMeta' => 
  array (
    'created_at' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => false,
    ),
    'xau_usd' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'usd_rub' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'bid' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'ask' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '12,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'netchange_pct' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
    'source' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '32',
      'phptype' => 'string',
      'null' => false,
      'default' => '',
    ),
    'raw' => 
    array (
      'dbtype' => 'text',
      'phptype' => 'string',
      'null' => true,
    ),
  ),
  'indexes' => 
  array (
    'created_at' => 
    array (
      'alias' => 'created_at',
      'primary' => false,
      'unique' => false,
      'type' => 'BTREE',
      'columns' => 
      array (
        'created_at' => 
        array (
          'length' => '',
          'collation' => 'A',
          'null' => false,
        ),
      ),
    ),
  ),
);
