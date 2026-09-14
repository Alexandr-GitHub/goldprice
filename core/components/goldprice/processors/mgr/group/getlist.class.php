<?php

use GoldPrice\Mgr\GroupTree;

class GoldPriceMgrGroupGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceGroup';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'weight';
    public $defaultSortDirection = 'ASC';
    public $permission = 'settings';
    public $objectType = 'goldprice.group';

    /** @var array<int,string> */
    private $titleById = [];

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $this->titleById = [];
        foreach ($this->modx->getCollection('GoldPriceGroup') as $object) {
            $this->titleById[(int) $object->get('id')] = (string) $object->get('title');
        }

        return parent::prepareQueryBeforeCount($c);
    }

    public function prepareRow(xPDOObject $object)
    {
        $row = $object->toArray();
        $parentId = isset($row['parent_id']) ? (int) $row['parent_id'] : 0;
        $row['parent_id'] = $parentId > 0 ? $parentId : null;
        $row['parent_title'] = $parentId > 0 && isset($this->titleById[$parentId])
            ? $this->titleById[$parentId]
            : '';
        $row['level'] = $parentId > 0 ? 1 : 0;

        return $row;
    }

    public function outputArray(array $array, $count = false)
    {
        return parent::outputArray(GroupTree::order($array), $count);
    }
}

return 'GoldPriceMgrGroupGetListProcessor';
