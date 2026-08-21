<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

class QuoteStaleException extends QuoteException
{
    /** @var Quote|null */
    private $quote;

    public function __construct(string $message, ?Quote $quote = null, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
        $this->quote = $quote;
    }

    public function getQuote(): ?Quote
    {
        return $this->quote;
    }
}
