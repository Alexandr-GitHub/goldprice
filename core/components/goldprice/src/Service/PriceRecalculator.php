<?php
declare(strict_types=1);

namespace GoldPrice\Service;

use GoldPrice;
use GoldPrice\Domain\Pricing\PriceBatchPlanner;
use GoldPrice\Domain\Quote\Quote;

/**
 * Loads one batch per entity, delegates calculation, then applies idempotent upserts.
 */
final class PriceRecalculator
{
    /** @var \modX */
    private $modx;

    /** @var GoldPrice */
    private $goldprice;

    public function __construct(\modX $modx, GoldPrice $goldprice)
    {
        $this->modx = $modx;
        $this->goldprice = $goldprice;
    }

    /**
     * @return array<string,mixed>
     */
    public function recalculate(): array
    {
        if (!$this->goldprice->initialize()) {
            return $this->error('Не удалось инициализировать пакет goldprice');
        }

        $quoteObject = $this->latestQuote();
        if ($quoteObject === null) {
            return $this->error('Нет сохранённой котировки');
        }

        try {
            $quote = Quote::fromRow($quoteObject->toArray());
        } catch (\Throwable $e) {
            return $this->error('Не удалось прочитать последнюю котировку: ' . $e->getMessage());
        }

        $productObjects = $this->modx->getCollection('GoldPriceProduct');
        $groupObjects = $this->modx->getCollection('GoldPriceGroup');
        $priceObjects = $this->modx->getCollection('GoldPricePrice');

        $products = [];
        foreach ($productObjects as $object) {
            $products[] = $object->toArray();
        }
        $groups = [];
        foreach ($groupObjects as $object) {
            $row = $object->toArray();
            $groups[(int) $row['id']] = $row;
        }
        $prices = [];
        $priceObjectByProduct = [];
        foreach ($priceObjects as $object) {
            $row = $object->toArray();
            $productId = (int) $row['product_id'];
            $prices[$productId] = $row;
            $priceObjectByProduct[$productId] = $object;
        }

        $quoteMaxAge = max(1, (int) $this->modx->getOption('goldprice.quote_max_age', null, 900));
        $usdMaxAge = max(1, (int) $this->modx->getOption('goldprice.usd_max_age', null, 86400));
        $now = time();

        // A stale quote is no evidence about the market, so it must not move the storm state either
        $storm = $quote->isGoldFresh($quoteMaxAge, $now) && $quote->isUsdFresh($usdMaxAge, $now)
            ? (new StormService($this->modx, $this->goldprice))->evaluate($now)
            : ['by_group' => [], 'modes' => [], 'events' => [], 'change_pct' => null];

        $summary = PriceBatchPlanner::plan(
            $quote,
            $products,
            $groups,
            $prices,
            (float) $this->modx->getOption('goldprice.vat_pct', null, 22),
            $quoteMaxAge,
            $usdMaxAge,
            $now,
            $storm['by_group']
        );
        $summary['storm'] = [
            'change_pct' => $storm['change_pct'],
            'modes' => $storm['modes'],
            'events' => $storm['events'],
        ];

        if (!$summary['ok']) {
            if (empty($summary['stale'])) {
                $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] ' . $summary['message']);
                $this->writeLog('price_recalculate_error', $summary['message'], $summary);
            }
            return $summary;
        }

        $now = date('Y-m-d H:i:s');
        foreach ($summary['prices'] as $productId => $row) {
            $object = $priceObjectByProduct[$productId] ?? $this->modx->newObject('GoldPricePrice');
            if (!$object) {
                return $this->error('Не удалось создать цену товара #' . $productId, $summary);
            }
            $row['updated_at'] = $now;
            $object->fromArray($row);
            if (!$object->save()) {
                return $this->error('Не удалось сохранить цену товара #' . $productId, $summary);
            }
        }

        $removed = 0;
        foreach ($summary['obsolete'] as $productId) {
            $object = $priceObjectByProduct[$productId] ?? null;
            if ($object && $object->remove()) {
                ++$removed;
            }
        }
        $summary['removed'] = $removed;

        $summary['message'] = sprintf(
            'Посчитано: %d; обновлено: %d; придержано: %d; пропущено: %d; снято с витрины: %d',
            $summary['calculated'],
            $summary['updated'],
            $summary['held'],
            $summary['skipped'],
            $removed
        );
        if ($storm['modes'] !== []) {
            $summary['message'] .= sprintf(
                '; Шторм: %s (изменение %s%%), заморожено продаж: %d, выкупов: %d',
                implode(', ', array_map(
                    static function ($groupId, $mode) {
                        return '#' . $groupId . ' ' . $mode;
                    },
                    array_keys($storm['modes']),
                    $storm['modes']
                )),
                $storm['change_pct'] === null
                    ? 'н/д'
                    : number_format((float) $storm['change_pct'], 2, '.', ''),
                $summary['frozen_sale'],
                $summary['frozen_buy']
            );
        }
        $this->writeLog('price_recalculate', $summary['message'], $summary);

        return $summary;
    }

    private function latestQuote()
    {
        $query = $this->modx->newQuery('GoldPriceQuote');
        $query->sortby('created_at', 'DESC');
        $query->limit(1);
        $rows = $this->modx->getCollection('GoldPriceQuote', $query);

        return $rows ? reset($rows) : null;
    }

    /**
     * @param array<string,mixed> $data
     */
    private function writeLog(string $event, string $message, array $data): bool
    {
        $log = $this->modx->newObject('GoldPriceLog');
        if (!$log) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot create summary log');
            return false;
        }
        unset($data['prices']);
        $log->fromArray([
            'created_at' => date('Y-m-d H:i:s'),
            'event' => $event,
            'user_id' => null,
            'message' => $message,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (!$log->save()) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Failed saving summary log');
            return false;
        }

        return true;
    }

    /**
     * @param array<string,mixed> $summary
     * @return array<string,mixed>
     */
    private function error(string $message, array $summary = []): array
    {
        $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] ' . $message);
        $result = array_merge([
            'ok' => false,
            'stale' => false,
            'message' => $message,
            'calculated' => 0,
            'updated' => 0,
            'held' => 0,
            'skipped' => 0,
            'skipped_reasons' => [],
            'prices' => [],
        ], $summary, ['ok' => false, 'message' => $message]);
        $this->writeLog('price_recalculate_error', $message, $result);

        return $result;
    }
}
