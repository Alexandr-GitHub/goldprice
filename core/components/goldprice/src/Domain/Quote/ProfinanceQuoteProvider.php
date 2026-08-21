<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Quote;

/**
 * Live Profinance request API via cURL + CURLOPT_INTERFACE.
 */
final class ProfinanceQuoteProvider implements QuoteProviderInterface
{
    /** @var string */
    private $url;

    /** @var string */
    private $sid;

    /** @var string */
    private $tickers;

    /** @var string */
    private $bindIp;

    /** @var int */
    private $timeout;

    /** @var int */
    private $goldMaxAgeSeconds;

    /** @var int */
    private $usdMaxAgeSeconds;

    /** @var ProfinanceResponseParser */
    private $parser;

    /**
     * @param array{url?:string,sid?:string,tickers?:string,bind_ip?:string,timeout?:int|string,max_age?:int|string,usd_max_age?:int|string} $config
     */
    public function __construct(array $config)
    {
        $this->url = rtrim((string) ($config['url'] ?? ''), '/');
        $this->sid = (string) ($config['sid'] ?? '');
        $this->tickers = (string) ($config['tickers'] ?? 'gold;USDRUB');
        $this->bindIp = (string) ($config['bind_ip'] ?? '');
        $this->timeout = max(1, (int) ($config['timeout'] ?? 10));
        $this->goldMaxAgeSeconds = max(1, (int) ($config['max_age'] ?? 900));
        $this->usdMaxAgeSeconds = max(1, (int) ($config['usd_max_age'] ?? 86400));

        $expected = array_values(array_filter(array_map('trim', explode(';', $this->tickers))));
        if ($expected === []) {
            $expected = ['gold', 'USDRUB'];
        }
        $this->parser = new ProfinanceResponseParser($expected);
    }

    public function fetchQuote(): Quote
    {
        if ($this->url === '' || $this->sid === '') {
            throw new QuoteTransportException('Profinance URL or SID is not configured');
        }
        if ($this->bindIp === '') {
            throw new QuoteTransportException('Profinance bind IP is not configured');
        }

        $endpoint = $this->url . '/?sid=' . rawurlencode($this->sid)
            . '&tickers=' . rawurlencode($this->tickers);

        $ch = curl_init($endpoint);
        if ($ch === false) {
            throw new QuoteTransportException('Failed to init cURL');
        }

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_INTERFACE, $this->bindIp);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $error = curl_error($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            throw new QuoteTransportException('cURL transport error: ' . $error);
        }
        if ($httpCode !== 200) {
            throw new QuoteTransportException('HTTP ' . $httpCode . ' from Profinance (non-200)');
        }
        if (!is_string($body) || $body === '') {
            throw new QuoteTransportException('Empty Profinance response body');
        }

        return $this->parser->parse(
            $body,
            $this->goldMaxAgeSeconds,
            $this->usdMaxAgeSeconds,
            time(),
            strlen($this->sid)
        );
    }
}
