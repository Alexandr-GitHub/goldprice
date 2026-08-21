<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * Server-side validation for goldprice_product form / API payload.
 *
 * Sign contract: custom_pct / custom_buy_pct are percent-of-base (1.3→+30, 0.8→−20), not discounts.
 */
final class ProductDataValidator
{
    /**
     * @param array $input raw form fields
     * @param int[] $allowedGroupIds
     * @return array{ok:bool,errors:string[],data:array}
     */
    public static function validate(array $input, array $allowedGroupIds)
    {
        $errors = [];
        $data = [
            'weight' => 0.0,
            'metal' => '',
            'coin_type' => '',
            'group_id' => null,
            'use_custom' => false,
            'custom_pct' => 0.0,
            'custom_buy_pct' => 0.0,
            'custom_fix' => 0.0,
            'custom_buy_fix' => 0.0,
            'ignore_market' => false,
            'fixed_price' => 0.0,
            'buyout_price' => 0.0,
        ];

        $weight = self::parseNonNegativeFloat(isset($input['weight']) ? $input['weight'] : '0', 'Вес');
        if (is_string($weight)) {
            $errors[] = $weight;
        } else {
            $data['weight'] = $weight;
        }

        $metalRaw = isset($input['metal']) ? trim((string) $input['metal']) : '';
        if ($metalRaw !== '') {
            $metal = mb_strtolower($metalRaw, 'UTF-8');
            if (!in_array($metal, ProductCatalog::METALS, true)) {
                $errors[] = 'Металл должен быть «золото» или «серебро».';
            } else {
                $data['metal'] = $metal;
            }
        }

        $coinRaw = isset($input['coin_type']) ? trim((string) $input['coin_type']) : '';
        if ($coinRaw !== '') {
            $coin = mb_strtolower($coinRaw, 'UTF-8');
            if (!in_array($coin, ProductCatalog::COIN_TYPES, true)) {
                $errors[] = 'Тип монеты должен быть «инвестиционные» или «памятные».';
            } else {
                $data['coin_type'] = $coin;
            }
        }

        $groupRaw = isset($input['group_id']) ? $input['group_id'] : '';
        if ($groupRaw === '' || $groupRaw === null || (string) $groupRaw === '0') {
            $data['group_id'] = null;
        } else {
            $gid = (int) $groupRaw;
            $allowed = array_map('intval', $allowedGroupIds);
            if (!in_array($gid, $allowed, true)) {
                $errors[] = 'Указана неизвестная весовая группа.';
            } else {
                $data['group_id'] = $gid;
            }
        }

        $data['use_custom'] = self::toBool(isset($input['use_custom']) ? $input['use_custom'] : 0);
        $data['ignore_market'] = self::toBool(isset($input['ignore_market']) ? $input['ignore_market'] : 0);

        foreach ([
            'custom_pct' => 'Процент собственной надбавки продажи',
            'custom_buy_pct' => 'Процент собственной надбавки выкупа',
        ] as $key => $label) {
            $pct = self::parseFloat(isset($input[$key]) ? $input[$key] : '0', $label);
            if (is_string($pct)) {
                $errors[] = $pct;
            } elseif ($pct < ProductCatalog::PCT_MIN || $pct > ProductCatalog::PCT_MAX) {
                $errors[] = sprintf(
                    '%s должен быть от %s до %s.',
                    $label,
                    ProductCatalog::PCT_MIN,
                    ProductCatalog::PCT_MAX
                );
            } else {
                $data[$key] = $pct;
            }
        }

        foreach (['custom_fix' => 'Фиксированная сумма надбавки продажи', 'fixed_price' => 'Фиксированная цена', 'buyout_price' => 'Цена выкупа'] as $key => $label) {
            $val = self::parseNonNegativeFloat(isset($input[$key]) ? $input[$key] : '0', $label);
            if (is_string($val)) {
                $errors[] = $val;
            } else {
                $data[$key] = $val;
            }
        }

        $buyFix = self::parseFloat(
            isset($input['custom_buy_fix']) ? $input['custom_buy_fix'] : '0',
            'Фиксированная сумма надбавки выкупа'
        );
        if (is_string($buyFix)) {
            $errors[] = $buyFix;
        } else {
            $data['custom_buy_fix'] = $buyFix;
        }

        return [
            'ok' => $errors === [],
            'errors' => $errors,
            'data' => $data,
        ];
    }

    /**
     * @param mixed $raw
     * @return float|string float on success, error string on failure
     */
    private static function parseNonNegativeFloat($raw, $label)
    {
        $v = self::parseFloat($raw, $label);
        if (is_string($v)) {
            return $v;
        }
        if ($v < 0) {
            return $label . ' не может быть отрицательным.';
        }

        return $v;
    }

    /**
     * @param mixed $raw
     * @return float|string
     */
    private static function parseFloat($raw, $label)
    {
        if ($raw === null || $raw === '') {
            return 0.0;
        }
        if (is_string($raw)) {
            $raw = str_replace(["\xc2\xa0", ' '], '', $raw);
            $raw = str_replace(',', '.', $raw);
        }
        if (!is_numeric($raw)) {
            return $label . ': некорректное число.';
        }

        return (float) $raw;
    }

    /**
     * @param mixed $raw
     */
    private static function toBool($raw)
    {
        if (is_bool($raw)) {
            return $raw;
        }
        if (is_string($raw)) {
            $raw = strtolower($raw);
            return in_array($raw, ['1', 'true', 'yes', 'on'], true);
        }

        return (int) $raw === 1;
    }
}
