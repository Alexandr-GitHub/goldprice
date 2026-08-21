<?php
class GoldPriceMgrRecalculateProcessor extends modProcessor
{
    public $permission = 'settings';

    public function process()
    {
        $gp = $this->modx->goldprice;
        if (!$gp || !$gp->initialize()) {
            return $this->failure($this->modx->lexicon('goldprice.err_init'));
        }

        $summary = $gp->recalculatePrices();
        if (empty($summary['ok'])) {
            return $this->failure(isset($summary['message']) ? $summary['message'] : $this->modx->lexicon('goldprice.err_recalculate'), $summary);
        }

        return $this->success($summary['message'], $summary);
    }
}

return 'GoldPriceMgrRecalculateProcessor';
