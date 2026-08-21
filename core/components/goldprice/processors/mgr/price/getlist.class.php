<?php

use GoldPrice\Mgr\CmpFormat;

class GoldPriceMgrPriceGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPricePrice';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'updated_at';
    public $defaultSortDirection = 'DESC';
    public $permission = 'settings';
    public $objectType = 'goldprice.price';

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $c->leftJoin('GoldPriceProduct', 'Product', 'GoldPricePrice.product_id = Product.product_id');
        $c->leftJoin('modResource', 'Resource', 'Product.product_id = Resource.id');
        $c->leftJoin('GoldPriceGroup', 'Group', 'GoldPricePrice.group_id = Group.id');

        $query = trim((string) $this->getProperty('query', ''));
        if ($query !== '') {
            $c->where([
                'Resource.pagetitle:LIKE' => '%' . $query . '%',
            ]);
        }

        return $c;
    }

    public function prepareQueryAfterCount(xPDOQuery $c)
    {
        $c->select($this->modx->getSelectColumns('GoldPricePrice', 'GoldPricePrice'));
        $c->select([
            'product_name' => 'Resource.pagetitle',
            'product_weight' => 'Product.weight',
            'group_title' => 'Group.title',
        ]);

        return $c;
    }

    public function prepareRow(xPDOObject $object)
    {
        $row = $object->toArray();
        $row['buy_price_display'] = CmpFormat::buyPriceDisplay(
            $row['buy_price'],
            $this->modx->lexicon('goldprice.buy_not_offered')
        );
        $row['sale_frozen'] = !empty($row['sale_frozen']) ? 1 : 0;
        $row['buy_frozen'] = !empty($row['buy_frozen']) ? 1 : 0;

        return $row;
    }
}

return 'GoldPriceMgrPriceGetListProcessor';
