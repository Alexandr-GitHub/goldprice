<?php

declare(strict_types=1);

namespace GoldPrice\Mgr;

/**
 * Display and export helpers for the manager CMP. No money is calculated here.
 */
final class CmpFormat
{
    public static function buyPriceDisplay($price, string $emptyLabel): string
    {
        if ((float) $price == 0.0) {
            return $emptyLabel;
        }

        return (string) $price;
    }

    /**
     * @param mixed $value
     */
    public static function sanitizeNumber($value): ?float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }
        $normalized = str_replace([' ', ','], ['', '.'], trim((string) $value));
        if ($normalized === '' || !is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * @param array<string,string> $before
     * @param array<string,string> $after
     * @return array<int,array{key:string,old:string,new:string}>
     */
    public static function settingDiffs(array $before, array $after): array
    {
        $diffs = [];
        foreach ($after as $key => $new) {
            $old = array_key_exists($key, $before) ? (string) $before[$key] : '';
            $new = (string) $new;
            if ($old !== $new) {
                $diffs[] = [
                    'key' => (string) $key,
                    'old' => self::maskSecret((string) $key, $old),
                    'new' => self::maskSecret((string) $key, $new),
                ];
            }
        }

        return $diffs;
    }

    /**
     * UTF-8 BOM + semicolon CSV so Excel on Windows opens Cyrillic.
     *
     * @param string[] $headers
     * @param array<int,array<string,mixed>> $rows
     */
    public static function csv(array $headers, array $rows): string
    {
        $out = "\xEF\xBB\xBF";
        $out .= self::csvLine($headers);
        foreach ($rows as $row) {
            $fields = [];
            foreach ($headers as $header) {
                $fields[] = $row[$header] ?? '';
            }
            $out .= self::csvLine($fields);
        }

        return $out;
    }

    /**
     * @param array<int,mixed> $fields
     */
    public static function csvLine(array $fields): string
    {
        $escaped = [];
        foreach ($fields as $field) {
            $value = str_replace('"', '""', (string) $field);
            $escaped[] = '"' . $value . '"';
        }

        return implode(';', $escaped) . "\r\n";
    }

    private static function maskSecret(string $key, string $value): string
    {
        if ($value !== '' && preg_match('/(?:^|[._])(?:sid|secret|password|token)(?:[._]|$)/i', $key)) {
            return '••••';
        }

        return $value;
    }

    public static function isDate(string $value): bool
    {
        return (bool) preg_match('/^\d{4}-\d{2}-\d{2}$/', $value);
    }
}
