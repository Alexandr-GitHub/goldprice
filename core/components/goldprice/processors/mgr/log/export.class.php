<?php

use GoldPrice\Mgr\CmpFormat;

class GoldPriceMgrLogExportProcessor extends modProcessor
{
    public $permission = 'settings';

    public function process()
    {
        if (!$this->modx->goldprice || !$this->modx->goldprice->initialize()) {
            return $this->failure($this->modx->lexicon('goldprice.err_init'));
        }

        $c = $this->modx->newQuery('GoldPriceLog');
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
        $c->sortby('created_at', 'DESC');

        $headers = ['created_at', 'event', 'user_id', 'message', 'data'];
        $rows = [];
        /** @var GoldPriceLog[] $logs */
        $logs = $this->modx->getCollection('GoldPriceLog', $c);
        foreach ($logs as $log) {
            $rows[] = [
                'created_at' => (string) $log->get('created_at'),
                'event' => (string) $log->get('event'),
                'user_id' => (string) $log->get('user_id'),
                'message' => (string) $log->get('message'),
                'data' => (string) $log->get('data'),
            ];
        }

        $csv = CmpFormat::csv($headers, $rows);
        $filename = 'goldprice-log-' . date('Y-m-d') . '.csv';

        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . strlen($csv));
        header('Cache-Control: no-store');
        echo $csv;
        exit;
    }
}

return 'GoldPriceMgrLogExportProcessor';
