<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Product;

/**
 * Pure TV → field parsers (no MODX / DB).
 */
final class TvValueParser
{
    /**
     * @param mixed $raw
     * @return float|null null = empty or unrecognized
     */
    public static function parseWeight($raw)
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }

        // Number + optional unit (г / гр / грамм); reject category phrases like «от 15.55».
        if (!preg_match('/^(-?\d+(?:[.,]\d+)?)\s*(?:г(?:р(?:амм)?)?\.?)?$/ui', $s, $m)) {
            return null;
        }

        $num = str_replace(',', '.', $m[1]);
        $v = (float) $num;
        if ($v < 0) {
            return null;
        }

        return $v;
    }

    /**
     * Weight source chain: grr → h_weight. newWeight is intentionally ignored (category, not grams).
     *
     * @param mixed $grr
     * @param mixed $hWeight
     * @return array{weight:?float,source:?string,status:string} status: ok|empty|unrecognized
     */
    public static function resolveWeight($grr, $hWeight)
    {
        $grrRaw = $grr === null ? '' : trim((string) $grr);
        if ($grrRaw !== '') {
            $w = self::parseWeight($grrRaw);
            if ($w === null) {
                return ['weight' => null, 'source' => 'grr', 'status' => 'unrecognized'];
            }

            return ['weight' => $w, 'source' => 'grr', 'status' => 'ok'];
        }

        $hRaw = $hWeight === null ? '' : trim((string) $hWeight);
        if ($hRaw !== '') {
            $w = self::parseWeight($hRaw);
            if ($w === null) {
                return ['weight' => null, 'source' => 'h_weight', 'status' => 'unrecognized'];
            }

            return ['weight' => $w, 'source' => 'h_weight', 'status' => 'ok'];
        }

        return ['weight' => null, 'source' => null, 'status' => 'empty'];
    }

    /**
     * @param mixed $raw
     * @return string|null null = unrecognized (incl. empty for migration logging)
     */
    public static function parseMetal($raw)
    {
        if ($raw === null) {
            return null;
        }
        $s = mb_strtolower(trim((string) $raw), 'UTF-8');
        if ($s === '') {
            return null;
        }
        if (in_array($s, ProductCatalog::METALS, true)) {
            return $s;
        }

        return null;
    }

    /**
     * @param mixed $raw
     * @return string|null '' = empty ok; null = unrecognized value
     */
    public static function parseCoinType($raw)
    {
        if ($raw === null) {
            return '';
        }
        $s = trim((string) $raw);
        if ($s === '' || $s === '[]') {
            return '';
        }

        if ($s[0] === '[') {
            $decoded = json_decode($s, true);
            if (!is_array($decoded)) {
                return null;
            }
            if ($decoded === []) {
                return '';
            }
            $s = (string) reset($decoded);
        }

        $s = mb_strtolower(trim($s), 'UTF-8');
        if ($s === '') {
            return '';
        }
        if (in_array($s, ProductCatalog::COIN_TYPES, true)) {
            return $s;
        }

        return null;
    }

    /**
     * @param mixed $raw
     * @return float|null null = empty or unrecognized
     */
    public static function parseBuyoutPrice($raw)
    {
        if ($raw === null) {
            return null;
        }
        $s = trim((string) $raw);
        if ($s === '') {
            return null;
        }

        $s = str_replace(["\xc2\xa0", ' '], '', $s);
        $s = str_replace(',', '.', $s);
        if (!is_numeric($s)) {
            return null;
        }
        $v = (float) $s;
        if ($v < 0) {
            return null;
        }

        return $v;
    }
}
