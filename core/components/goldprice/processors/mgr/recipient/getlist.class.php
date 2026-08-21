<?php
class GoldPriceMgrRecipientGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceRecipient';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'email';
    public $defaultSortDirection = 'ASC';
    public $permission = 'settings';
    public $objectType = 'goldprice.recipient';
}

return 'GoldPriceMgrRecipientGetListProcessor';
