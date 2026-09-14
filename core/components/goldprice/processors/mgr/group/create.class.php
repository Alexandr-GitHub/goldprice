<?php

use GoldPrice\Mgr\CmpFormat;
use GoldPrice\Mgr\GroupTrash;

class GoldPriceMgrGroupCreateProcessor extends modObjectCreateProcessor
{
    public $classKey = 'GoldPriceGroup';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.group';

    public function beforeSet()
    {
        if (!$this->getProperty('id')) {
            $this->unsetProperty('id');
        }

        $parentId = (int) $this->getProperty('parent_id', 0);
        if ($parentId <= 0) {
            return $this->beforeSetRoot();
        }

        return $this->beforeSetSubgroup($parentId);
    }

    private function beforeSetRoot()
    {
        $title = trim((string) $this->getProperty('title', ''));
        if ($title === '') {
            return $this->modx->lexicon('goldprice.err_group_title');
        }
        $this->setProperty('title', $title);
        $this->setProperty('parent_id', null);

        $weight = CmpFormat::sanitizeNumber($this->getProperty('weight', 0));
        if ($weight === null || (float) $weight <= 0) {
            return $this->modx->lexicon('goldprice.err_group_weight');
        }
        $this->setProperty('weight', $weight);

        foreach (['sale_markup', 'sale_fix', 'buy_discount', 'buy_fix', 'price_step', 'stoploss', 'min_margin'] as $field) {
            $value = CmpFormat::sanitizeNumber($this->getProperty($field, 0));
            if ($value === null) {
                return $this->modx->lexicon('goldprice.err_group_number', ['field' => $field]);
            }
            $this->setProperty($field, $value);
        }

        return parent::beforeSet();
    }

    private function beforeSetSubgroup(int $parentId)
    {
        /** @var GoldPriceGroup|null $parent */
        $parent = $this->modx->getObject('GoldPriceGroup', $parentId);
        if (!$parent || (int) $parent->get('parent_id') > 0 || GroupTrash::isDeleted($parent->toArray())) {
            return $this->modx->lexicon('goldprice.err_group_parent');
        }

        $title = trim((string) $this->getProperty('title', ''));
        if ($title === '') {
            return $this->modx->lexicon('goldprice.err_group_title');
        }
        $this->setProperty('title', $title);
        $this->setProperty('parent_id', $parentId);
        $this->setProperty('weight', (float) $parent->get('weight'));
        $this->setProperty('price_step', 0);
        $this->setProperty('stoploss', 0);
        $this->setProperty('min_margin', 0);

        foreach (['sale_markup', 'sale_fix', 'buy_discount', 'buy_fix'] as $field) {
            $value = CmpFormat::sanitizeNumber($this->getProperty($field, 0));
            if ($value === null) {
                return $this->modx->lexicon('goldprice.err_group_number', ['field' => $field]);
            }
            $this->setProperty($field, $value);
        }

        return parent::beforeSet();
    }

    public function afterSave()
    {
        $gp = $this->modx->goldprice;
        if ($gp) {
            $isRoot = (int) $this->object->get('parent_id') <= 0;
            if ($isRoot) {
                $gp->writeLog('group_root_create', $this->modx->lexicon('goldprice.log_group_root_create', [
                    'title' => (string) $this->object->get('title'),
                ]), [
                    'id' => (int) $this->object->get('id'),
                ]);
            } else {
                $gp->writeLog('group_subgroup_create', $this->modx->lexicon('goldprice.log_group_subgroup_create', [
                    'title' => (string) $this->object->get('title'),
                ]), [
                    'id' => (int) $this->object->get('id'),
                    'parent_id' => (int) $this->object->get('parent_id'),
                ]);
            }
            $this->setProperty('_recalc', $gp->recalculatePrices());
        }

        return parent::afterSave();
    }

    public function cleanup()
    {
        $summary = $this->getProperty('_recalc');
        if (is_array($summary)) {
            $message = isset($summary['message']) ? (string) $summary['message'] : '';

            return $this->success($message, $summary);
        }

        return parent::cleanup();
    }
}

return 'GoldPriceMgrGroupCreateProcessor';
