<?php
declare(strict_types=1);

namespace GoldPrice\Service;

use GoldPrice;
use GoldPrice\Domain\Quote\Quote;
use GoldPrice\Domain\Quote\QuoteException;
use GoldPrice\Domain\Quote\QuoteProviderInterface;
use GoldPrice\Domain\Quote\QuoteStaleException;

/**
 * Fetch quote → persist goldprice_quote → sync ClientConfig keys used by the storefront.
 */
final class QuoteUpdater
{
    /** @var \modX */
    private $modx;

    /** @var GoldPrice */
    private $goldprice;

    /** @var QuoteProviderInterface */
    private $provider;

    /** @var string */
    private $lockPath;

    public function __construct(
        \modX $modx,
        GoldPrice $goldprice,
        QuoteProviderInterface $provider,
        string $lockPath = ''
    ) {
        $this->modx = $modx;
        $this->goldprice = $goldprice;
        $this->provider = $provider;
        $this->lockPath = $lockPath !== ''
            ? $lockPath
            : rtrim((string) $modx->getOption('core_path'), '/') . '/cache/goldprice/quote-update.lock';
    }

    /**
     * @return array{ok:bool,stale:bool,skipped:bool,message:string,quote?:Quote}
     */
    public function update(): array
    {
        $lock = $this->acquireLock();
        if ($lock === null) {
            return [
                'ok' => false,
                'stale' => false,
                'skipped' => true,
                'message' => 'Another quote update is in progress',
            ];
        }

        try {
            if (!$this->goldprice->initialize()) {
                return $this->fail('Failed to initialize goldprice package', false);
            }

            $quote = null;
            $stale = false;
            try {
                $quote = $this->provider->fetchQuote();
            } catch (QuoteStaleException $e) {
                // Night / weekend / daily halt: last tick is older than quote_max_age.
                // Keep last prices, do not treat this as an API failure.
                $stale = true;
                $quote = $e->getQuote();
                if ($quote === null) {
                    return [
                        'ok' => false,
                        'stale' => true,
                        'skipped' => false,
                        'message' => $e->getMessage(),
                    ];
                }
            } catch (QuoteException $e) {
                $this->modx->log(
                    \modX::LOG_LEVEL_ERROR,
                    '[goldprice] Quote fetch failed: ' . $e->getMessage()
                );
                return $this->fail($e->getMessage(), false, true);
            }

            if (!$this->persistQuote($quote)) {
                return $this->fail('Failed to save goldprice_quote', $stale, !$stale);
            }

            // Persist the last tick for diagnostics; do not push it into ClientConfig.
            if ($stale) {
                return [
                    'ok' => true,
                    'stale' => true,
                    'skipped' => false,
                    'message' => 'Рынок не торгуется — котировка не обновлена.',
                    'quote' => $quote,
                ];
            }

            if (!$this->syncClientConfig($quote)) {
                return $this->fail('Failed to sync ClientConfig', false, true);
            }

            return [
                'ok' => true,
                'stale' => false,
                'skipped' => false,
                'message' => 'Quote updated',
                'quote' => $quote,
            ];
        } finally {
            $this->releaseLock($lock);
        }
    }

    /**
     * Identity for idempotent upsert: one row per quote timestamp + source.
     *
     * @return array{created_at:string,source:string}
     */
    public static function rowIdentity(Quote $quote): array
    {
        return [
            'created_at' => date('Y-m-d H:i:s', $quote->getQuotedAt()),
            'source' => 'profinance',
        ];
    }

    private function persistQuote(Quote $quote): bool
    {
        $identity = self::rowIdentity($quote);
        // Upsert in place: same jutcdt must not spawn a second storm-history point
        /** @var \GoldPriceQuote|null $row */
        $row = $this->modx->getObject('GoldPriceQuote', $identity);
        if (!$row) {
            $row = $this->modx->newObject('GoldPriceQuote');
            if (!$row) {
                $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot create GoldPriceQuote');
                return false;
            }
        }

        $row->fromArray([
            'created_at' => $identity['created_at'],
            'xau_usd' => $quote->getXauUsd(),
            'usd_rub' => $quote->getUsdRub(),
            'bid' => $quote->getBid(),
            'ask' => $quote->getAsk(),
            'netchange_pct' => $quote->getNetchangePct(),
            'source' => $identity['source'],
            'raw' => $quote->getRaw(),
        ]);

        if (!$row->save()) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] GoldPriceQuote::save failed');
            return false;
        }

        return true;
    }

    /**
     * Keys proven by sl.tpl / stock*.tpl / glavnaya.tpl + historical KursMetall2 writes.
     * Silver/EUR left untouched (later steps).
     */
    private function syncClientConfig(Quote $quote): bool
    {
        $modelPath = $this->modx->getOption('core_path') . 'components/clientconfig/model/';
        if (!$this->modx->addPackage('clientconfig', $modelPath)) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] clientconfig package missing');
            return false;
        }

        $map = $quote->storefrontRates();

        foreach ($map as $key => $value) {
            /** @var \cgSetting|null $cc */
            $cc = $this->modx->getObject('cgSetting', ['key' => $key]);
            if (!$cc) {
                $this->modx->log(
                    \modX::LOG_LEVEL_ERROR,
                    '[goldprice] ClientConfig key missing: ' . $key
                );
                return false;
            }
            $cc->set('value', $value);
            if (!$cc->save()) {
                $this->modx->log(
                    \modX::LOG_LEVEL_ERROR,
                    '[goldprice] Failed saving ClientConfig key: ' . $key
                );
                return false;
            }
        }

        $this->modx->cacheManager->clearCache(['/system_settings/clientconfig.cache.php']);

        return true;
    }

    /**
     * @return resource|null
     */
    private function acquireLock()
    {
        $dir = dirname($this->lockPath);
        if (!is_dir($dir) && !mkdir($dir, 0755, true) && !is_dir($dir)) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot create lock dir');
            return null;
        }

        $fh = fopen($this->lockPath, 'c+');
        if ($fh === false) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot open lock file');
            return null;
        }

        // Non-blocking exclusive lock — parallel cron runs bail out safely
        if (!flock($fh, LOCK_EX | LOCK_NB)) {
            fclose($fh);
            return null;
        }

        ftruncate($fh, 0);
        fwrite($fh, (string) getmypid());
        fflush($fh);

        return $fh;
    }

    /**
     * @param resource $lock
     */
    private function releaseLock($lock): void
    {
        flock($lock, LOCK_UN);
        fclose($lock);
    }

    /**
     * @return array{ok:bool,stale:bool,skipped:bool,message:string}
     */
    private function fail(string $message, bool $stale, bool $notify = false): array
    {
        // Do not mail on stale quotes — expected outside the trading session.
        if ($notify && !$stale) {
            $this->goldprice->notify('api_error', [
                'time' => date('Y-m-d H:i:s'),
                'message' => Notifier::safeApiMessage($message),
            ]);
        }

        return [
            'ok' => false,
            'stale' => $stale,
            'skipped' => false,
            'message' => $message,
        ];
    }
}
