<?php

use GoldPrice\Mgr\CmpFormat;

class GoldPriceMgrLogGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceLog';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'created_at';
    public $defaultSortDirection = 'DESC';
    public $permission = 'settings';
    public $objectType = 'goldprice.log';

    public function prepareQueryBeforeCount(xPDOQuery $c)
    {
        $event = trim((string) $this->getProperty('event', ''));
        if ($event !== '') {
            $c->where(['event' => $event]);
        }

        $dateStart = trim((string) $this->getProperty('date_start', ''));
        if ($dateStart !== '' && CmpFormat::isDate($dateStart)) {
            $c->where(['created_at:>=' => $dateStart . ' 00:00:00']);
        }

        $dateEnd = trim((string) $this->getProperty('date_end', ''));
        if ($dateEnd !== '' && CmpFormat::isDate($dateEnd)) {
            $c->where(['created_at:<=' => $dateEnd . ' 23:59:59']);
        }

        return $c;
    }
}

return 'GoldPriceMgrLogGetListProcessor';
