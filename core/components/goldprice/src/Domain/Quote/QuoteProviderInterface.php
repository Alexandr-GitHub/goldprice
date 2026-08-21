<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

/**
 * Quote data source — returns a parsed Quote or throws QuoteException.
 */
interface QuoteProviderInterface
{
    /**
     * @throws QuoteException
     */
    public function fetchQuote(): Quote;
}
