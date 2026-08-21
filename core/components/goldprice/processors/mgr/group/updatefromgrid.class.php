<?php

use GoldPrice\Mgr\CmpFormat;

class GoldPriceMgrGroupUpdateFromGridProcessor extends modObjectUpdateProcessor
{
    public $classKey = 'GoldPriceGroup';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.group';

    /** @var array<string,mixed> */
    private $before = [];

    public function initialize()
    {
        $data = $this->getProperty('data');
        if (empty($data)) {
            return $this->modx->lexicon('invalid_data');
        }
        $data = is_array($data) ? $data : $this->modx->fromJSON($data);
        if (empty($data) || empty($data['id'])) {
            return $this->modx->lexicon('invalid_data');
        }
        $this->setProperties($data);
        $this->unsetProperty('data');

        return parent::initialize();
    }

    public function beforeSet()
    {
        $this->before = $this->object->toArray();

        $title = trim((string) $this->getProperty('title', $this->object->get('title')));
        if ($title === '') {
            return $this->modx->lexicon('goldprice.err_group_title');
        }
        $this->setProperty('title', $title);

        $numeric = [
            'weight',
            'sale_markup',
            'sale_fix',
            'buy_discount',
            'buy_fix',
            'price_step',
            'stoploss',
            'min_margin',
        ];
        foreach ($numeric as $field) {
            $value = CmpFormat::sanitizeNumber($this->getProperty($field, $this->object->get($field)));
            if ($value === null) {
                return $this->modx->lexicon('goldprice.err_group_number', ['field' => $field]);
            }
            $this->setProperty($field, $value);
        }

        if ((float) $this->getProperty('weight') <= 0) {
            return $this->modx->lexicon('goldprice.err_group_weight');
        }

        return parent::beforeSet();
    }

    public function afterSave()
    {
        $after = $this->object->toArray();
        $gp = $this->modx->goldprice;
        if ($gp) {
            $gp->writeLog('group_update', $this->modx->lexicon('goldprice.log_group_update', [
                'title' => $after['title'],
            ]), [
                'id' => (int) $after['id'],
                'before' => $this->before,
                'after' => $after,
            ]);
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

return 'GoldPriceMgrGroupUpdateFromGridProcessor';
