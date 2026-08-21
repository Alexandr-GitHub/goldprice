<?php

use GoldPrice\Domain\Quote\Quote;

class GoldPriceMgrQuoteGetListProcessor extends modObjectGetListProcessor
{
    public $classKey = 'GoldPriceQuote';
    public $languageTopics = ['goldprice:default'];
    public $defaultSortField = 'created_at';
    public $defaultSortDirection = 'DESC';
    public $permission = 'settings';
    public $objectType = 'goldprice.quote';

    public function prepareRow(xPDOObject $object)
    {
        $row = $object->toArray();
        try {
            $quote = Quote::fromRow($row);
            $row['gold'] = number_format($quote->goldRubPerGram(), 2, '.', '');
            $row['gold_delta'] = number_format($quote->goldDeltaRub(), 2, '.', '');
            $row['usd_delta'] = number_format($quote->getNetchangeUsd(), 4, '.', '');
        } catch (\Throwable $e) {
            $row['gold'] = '';
            $row['gold_delta'] = '';
            $row['usd_delta'] = '';
        }
        unset($row['raw']);

        return $row;
    }
}

return 'GoldPriceMgrQuoteGetListProcessor';
