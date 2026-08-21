<?php
$xpdo_meta_map['GoldPriceState']= array (
  'package' => 'goldprice',
  'version' => '1.1',
  'table' => 'goldprice_state',
  'extends' => 'xPDOSimpleObject',
  'tableMeta' => 
  array (
    'engine' => 'InnoDB',
  ),
  'fields' => 
  array (
    'group_id' => NULL,
    'mode' => 'normal',
    'started_at' => NULL,
    'expires_at' => NULL,
    'change_pct' => 0.0,
  ),
  'fieldMeta' => 
  array (
    'group_id' => 
    array (
      'dbtype' => 'int',
      'precision' => '10',
      'attributes' => 'unsigned',
      'phptype' => 'integer',
      'null' => false,
    ),
    'mode' => 
    array (
      'dbtype' => 'varchar',
      'precision' => '16',
      'phptype' => 'string',
      'null' => false,
      'default' => 'normal',
    ),
    'started_at' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => false,
    ),
    'expires_at' => 
    array (
      'dbtype' => 'datetime',
      'phptype' => 'datetime',
      'null' => true,
    ),
    'change_pct' => 
    array (
      'dbtype' => 'decimal',
      'precision' => '8,4',
      'phptype' => 'float',
      'null' => false,
      'default' => 0.0,
    ),
  ),
  'indexes' => 
  array (
    'group_id' => 
    array (
      'alias' => 'group_id',
      'primary' => false,
      'unique' => true,
      'type' => 'BTREE',
      'columns' => 
      array (
        'group_id' => 
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
