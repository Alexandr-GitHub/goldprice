<?php
class GoldPriceMgrGroupGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceGroup';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'weight';
    public $defaultSortDirection = 'ASC';
    public $permission = 'settings';
    public $objectType = 'goldprice.group';
}

return 'GoldPriceMgrGroupGetListProcessor';
