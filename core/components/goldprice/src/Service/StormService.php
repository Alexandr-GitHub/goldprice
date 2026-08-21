<?php
declare(strict_types=1);

namespace GoldPrice\Service;

use GoldPrice;
use GoldPrice\Domain\Storm\StormDecision;
use GoldPrice\Domain\Storm\StormDetector;

/**
 * Storm state per weight group (ТЗ п.6.2): reads quote history and goldprice_state,
 * asks the detector, persists the decision and journals every transition.
 *
 * goldprice_state holds active storms only — a released mode is deleted, and the
 * storm_off entry in goldprice_log is the record that it happened.
 */
final class StormService
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
     * @return array<string,mixed> by_group feeds PriceBatchPlanner, the rest is reporting
     */
    public function evaluate(int $now): array
    {
        $window = max(60, (int) $this->modx->getOption('goldprice.storm_window', null, 3600));
        $duration = max(60, (int) $this->modx->getOption('goldprice.storm_duration', null, 3600));

        $result = [
            'by_group' => [],
            'modes' => [],
            'events' => [],
            'change_pct' => null,
            'window' => $window,
            'duration' => $duration,
        ];

        if (!$this->goldprice->initialize()) {
            return $result;
        }

        $changePct = StormDetector::changePct($this->history($window, $now), $window, $now);
        $result['change_pct'] = $changePct === null ? null : self::decimal($changePct);

        $states = [];
        foreach ($this->modx->getCollection('GoldPriceState') as $object) {
            $states[(int) $object->get('group_id')] = $object;
        }

        foreach ($this->modx->getCollection('GoldPriceGroup') as $group) {
            $groupId = (int) $group->get('id');
            $state = $states[$groupId] ?? null;
            $decision = StormDetector::decide(
                $changePct,
                (float) $group->get('stoploss'),
                $state === null ? null : $state->toArray(),
                $duration,
                $now
            );

            if ($decision->isActive()) {
                $this->saveState($groupId, $decision, $state);
                $result['by_group'][$groupId] = [
                    'sale' => $decision->shouldFreezeSale(),
                    'buy' => $decision->shouldFreezeBuy(),
                    'reason' => $decision->getReason(),
                ];
                $result['modes'][$groupId] = $decision->getMode();
            } elseif ($state !== null && !$state->remove()) {
                $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Не удалось снять режим группы #' . $groupId);
            }

            if ($decision->getEvent() !== StormDecision::EVENT_NONE) {
                $result['events'][] = [
                    'event' => $decision->getEvent(),
                    'group_id' => $groupId,
                    'title' => (string) $group->get('title'),
                    'mode' => $decision->getMode(),
                    'change_pct' => $decision->getChangePct(),
                    'freeze_sale' => $decision->shouldFreezeSale(),
                    'freeze_buy' => $decision->shouldFreezeBuy(),
                ];
                $this->writeLog($decision, $groupId, (string) $group->get('title'), (float) $group->get('stoploss'));
            }
        }

        // Aggregate after the loop: one mail per event type, not one per group.
        $this->notifyAggregated($result);

        return $result;
    }

    /**
     * Flip is mailed as storm_on (new mode). Pure OFF groups get storm_off.
     *
     * @param array<string,mixed> $result
     */
    private function notifyAggregated(array $result): void
    {
        $on = [];
        $off = [];
        foreach ($result['events'] as $ev) {
            if (!is_array($ev) || empty($ev['event'])) {
                continue;
            }
            if ($ev['event'] === StormDecision::EVENT_ON || $ev['event'] === StormDecision::EVENT_FLIP) {
                $on[] = $ev;
            } elseif ($ev['event'] === StormDecision::EVENT_OFF) {
                $off[] = $ev;
            }
        }

        $base = [
            'change_pct' => $result['change_pct'],
            'time' => date('Y-m-d H:i:s'),
        ];
        if ($on) {
            $this->goldprice->notify('storm_on', array_merge($base, ['groups' => $on]));
        }
        if ($off) {
            $this->goldprice->notify('storm_off', array_merge($base, ['groups' => $off]));
        }
    }

    /**
     * Quotes that can serve as window baseline: the detector ignores anything
     * older than two windows anyway.
     *
     * @return array<int,array<string,mixed>>
     */
    private function history(int $window, int $now): array
    {
        $query = $this->modx->newQuery('GoldPriceQuote');
        $query->where(['created_at:>=' => date('Y-m-d H:i:s', $now - $window * 2)]);
        $query->sortby('created_at', 'ASC');

        $rows = [];
        foreach ($this->modx->getCollection('GoldPriceQuote', $query) as $object) {
            $rows[] = [
                'created_at' => $object->get('created_at'),
                'xau_usd' => $object->get('xau_usd'),
                'usd_rub' => $object->get('usd_rub'),
            ];
        }

        return $rows;
    }

    /**
     * @param \xPDOObject|null $state
     */
    private function saveState(int $groupId, StormDecision $decision, $state): bool
    {
        if ($state === null) {
            $state = $this->modx->newObject('GoldPriceState');
            if (!$state) {
                $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot create GoldPriceState');
                return false;
            }
        }

        $state->fromArray([
            'group_id' => $groupId,
            'mode' => $decision->getMode(),
            'started_at' => date('Y-m-d H:i:s', (int) $decision->getStartedAt()),
            'expires_at' => date('Y-m-d H:i:s', (int) $decision->getExpiresAt()),
            // MODX runs under ru_RU, where PHP 7.4 renders a float as "-8,6957" — decimals go in as strings
            'change_pct' => self::decimal((float) $decision->getChangePct()),
        ]);

        if (!$state->save()) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Не удалось сохранить режим группы #' . $groupId);
            return false;
        }

        return true;
    }

    private static function decimal(float $value): string
    {
        return number_format($value, 4, '.', '');
    }

    private function writeLog(StormDecision $decision, int $groupId, string $title, float $stoploss): bool
    {
        $log = $this->modx->newObject('GoldPriceLog');
        if (!$log) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Cannot create storm log');
            return false;
        }

        $message = sprintf(
            'Группа #%d %s: %s',
            $groupId,
            $title === '' ? '(без названия)' : $title,
            $decision->getReason()
        );
        $log->fromArray([
            'created_at' => date('Y-m-d H:i:s'),
            'event' => $decision->getEvent(),
            'user_id' => null,
            'message' => $message,
            'data' => json_encode([
                'group_id' => $groupId,
                'mode' => $decision->getMode(),
                'change_pct' => $decision->getChangePct(),
                'stoploss' => $stoploss,
                'started_at' => $decision->getStartedAt(),
                'expires_at' => $decision->getExpiresAt(),
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        if (!$log->save()) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] Failed saving storm log');
            return false;
        }

        return true;
    }
}
