<?php
class GoldPriceMgrRequestGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceRequest';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'created_at';
    public $defaultSortDirection = 'DESC';
    public $permission = 'settings';
    public $objectType = 'goldprice.request';

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c->leftJoin('modResource', 'Resource', 'GoldPriceRequest.product_id = Resource.id');
        $status = trim((string) $this->getProperty('status', ''));
        if ($status !== '') {
            $c->where(['status' => $status]);
        }

        return $c;
    }

    public function prepareQueryAfterCount(xPDOQuery $c)
    {
        $c->select($this->modx->getSelectColumns('GoldPriceRequest', 'GoldPriceRequest'));
        $c->select([
            'product_name' => 'Resource.pagetitle',
        ]);

        return $c;
    }
}

return 'GoldPriceMgrRequestGetListProcessor';
