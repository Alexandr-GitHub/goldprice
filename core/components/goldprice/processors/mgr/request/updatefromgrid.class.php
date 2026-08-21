<?php
class GoldPriceMgrRequestUpdateFromGridProcessor extends modObjectUpdateProcessor
{
    public $classKey = 'GoldPriceRequest';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.request';

    public const STATUSES = ['new', 'processing', 'done', 'cancelled'];

    /** @var string */
    protected $previousStatus = '';

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
        $this->previousStatus = (string) $this->object->get('status');
        $status = (string) $this->getProperty('status', $this->previousStatus);
        if (!in_array($status, self::STATUSES, true)) {
            return $this->modx->lexicon('goldprice.err_request_status');
        }
        $this->setProperty('status', $status);

        foreach (array_keys($this->getProperties()) as $key) {
            if (!in_array($key, ['id', 'status', 'manager_id'], true)) {
                $this->unsetProperty($key);
            }
        }

        if ($status !== $this->previousStatus && $this->modx->user) {
            $this->setProperty('manager_id', (int) $this->modx->user->get('id'));
        }

        return parent::beforeSet();
    }

    public function afterSave()
    {
        /** @var GoldPrice|null $gp */
        $gp = !empty($this->modx->goldprice) ? $this->modx->goldprice : null;
        if (!$gp) {
            return parent::afterSave();
        }

        $status = (string) $this->object->get('status');
        $requestId = (int) $this->object->get('id');
        $gp->writeLog('request_status', $this->modx->lexicon('goldprice.log_request_status', [
            'id' => $requestId,
            'status' => $status,
        ]), [
            'id' => $requestId,
            'status' => $status,
        ]);

        if ($status === 'done' && $this->previousStatus !== 'done') {
            $dailyLimit = (float) $this->modx->getOption('goldprice.daily_buyout_limit', null, 0);
            // After save this row is included in the sum.
            $doneToday = $gp->sumDoneBuyoutToday();
            if (\GoldPrice\Domain\Buyout\BuyoutLimits::isDailyExceeded(0.0, $doneToday, $dailyLimit)) {
                $gp->notify('daily_limit', [
                    'date' => date('Y-m-d'),
                    'time' => date('H:i:s'),
                    'sum' => \GoldPrice\Domain\Money::roundMoney($doneToday),
                    'limit' => $dailyLimit,
                    'request_id' => $requestId,
                    'context' => 'approve',
                ]);
            }
        }

        return parent::afterSave();
    }
}

return 'GoldPriceMgrRequestUpdateFromGridProcessor';
