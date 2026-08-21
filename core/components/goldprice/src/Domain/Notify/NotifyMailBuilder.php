<?php
declare(strict_types=1);

namespace GoldPrice\Domain\Notify;

/**
 * Pure builder: subject / HTML / plaintext from event + data + lexicon labels.
 * No $modx — template HTML is passed in (file read by Notifier).
 */
final class NotifyMailBuilder
{
    /**
     * Event → recipient column flag (storm_flip is mailed as storm_on upstream).
     *
     * @var array<string,string>
     */
    public static $eventFlags = [
        'storm_on' => 'storm_on',
        'storm_off' => 'storm_off',
        'daily_limit' => 'daily_limit',
        'api_error' => 'api_error',
        'request_new' => 'new_request',
        'mail_test' => '', // not subscribed; explicit address only
    ];

    /**
     * @param array<string,string> $lex lexicon snippets (already loaded)
     * @param array{logo_url?:string,logo_alt?:string,site_name?:string,mgr_url?:string,template?:string} $meta
     * @return array{subject:string,html:string,text:string}
     */
    public static function build(string $event, array $data, array $lex, array $meta = []): array
    {
        $rows = self::rows($event, $data, $lex, $meta);
        $title = self::title($event, $lex);
        $subject = self::subject($event, $data, $lex);

        $rowsHtml = '';
        $textLines = [$title, ''];
        foreach ($rows as $row) {
            $label = self::esc($row['label']);
            $value = self::esc($row['value']);
            $rowsHtml .= '<tr>'
                . '<td style="padding:8px 12px;border:1px solid #E5E5E5;background:#fafafa;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#333;width:40%;">'
                . $label . '</td>'
                . '<td style="padding:8px 12px;border:1px solid #E5E5E5;font-family:Arial,Helvetica,sans-serif;font-size:14px;color:#111;">'
                . $value . '</td>'
                . '</tr>';
            $textLines[] = $row['label'] . ': ' . $row['value'];
        }

        $logoUrl = isset($meta['logo_url']) ? (string) $meta['logo_url'] : '';
        $logoAlt = isset($meta['logo_alt']) ? (string) $meta['logo_alt'] : 'Logo';
        $siteName = isset($meta['site_name']) ? (string) $meta['site_name'] : 'Golden Pearl';

        $template = isset($meta['template']) ? (string) $meta['template'] : self::fallbackTemplate();
        $html = strtr($template, [
            '[[+logo_url]]' => self::esc($logoUrl),
            '[[+logo_alt]]' => self::esc($logoAlt),
            '[[+title]]' => self::esc($title),
            '[[+rows]]' => $rowsHtml,
            '[[+site_name]]' => self::esc($siteName),
            '[[+accent]]' => '#dbc152',
        ]);

        return [
            'subject' => $subject,
            'html' => $html,
            'text' => implode("\n", $textLines),
        ];
    }

    /**
     * @param array<string,string> $lex
     * @param array<string,mixed> $meta
     * @return array<int,array{label:string,value:string}>
     */
    public static function rows(string $event, array $data, array $lex, array $meta = []): array
    {
        if ($event === 'storm_on' || $event === 'storm_off') {
            return self::stormRows($event, $data, $lex);
        }
        if ($event === 'request_new') {
            return self::requestRows($data, $lex, $meta);
        }
        if ($event === 'daily_limit') {
            return [
                ['label' => self::l($lex, 'mail_label_date'), 'value' => self::s($data, 'date')],
                ['label' => self::l($lex, 'mail_label_time'), 'value' => self::s($data, 'time')],
                ['label' => self::l($lex, 'mail_label_sum'), 'value' => self::s($data, 'sum')],
                ['label' => self::l($lex, 'mail_label_limit'), 'value' => self::s($data, 'limit')],
            ];
        }
        if ($event === 'api_error') {
            return [
                ['label' => self::l($lex, 'mail_label_time'), 'value' => self::s($data, 'time')],
                ['label' => self::l($lex, 'mail_label_message'), 'value' => self::s($data, 'message')],
            ];
        }
        if ($event === 'mail_test') {
            return [
                ['label' => self::l($lex, 'mail_label_time'), 'value' => self::s($data, 'time', date('Y-m-d H:i:s'))],
                ['label' => self::l($lex, 'mail_label_message'), 'value' => self::l($lex, 'mail_test_body')],
            ];
        }

        return [
            ['label' => 'event', 'value' => $event],
        ];
    }

    /**
     * @param array<string,string> $lex
     */
    public static function title(string $event, array $lex): string
    {
        $label = self::l($lex, 'mail_title_' . $event);
        if ($label !== 'mail_title_' . $event) {
            return $label;
        }

        return $event;
    }

    /**
     * @param array<string,string> $lex
     */
    public static function subject(string $event, array $data, array $lex): string
    {
        $title = self::title($event, $lex);
        if ($event === 'request_new' && isset($data['id'])) {
            return $title . ' #' . (int) $data['id'];
        }
        if (($event === 'storm_on' || $event === 'storm_off') && isset($data['change_pct']) && $data['change_pct'] !== null && $data['change_pct'] !== '') {
            return $title . ' (' . self::s($data, 'change_pct') . '%)';
        }

        return $title;
    }

    /**
     * @param array<string,string> $lex
     * @return array<int,array{label:string,value:string}>
     */
    private static function stormRows(string $event, array $data, array $lex): array
    {
        $rows = [
            ['label' => self::l($lex, 'mail_label_event'), 'value' => self::title($event, $lex)],
            ['label' => self::l($lex, 'mail_label_time'), 'value' => self::s($data, 'time', date('Y-m-d H:i:s'))],
            ['label' => self::l($lex, 'mail_label_change_pct'), 'value' => self::s($data, 'change_pct') . '%'],
        ];

        $groups = isset($data['groups']) && is_array($data['groups']) ? $data['groups'] : [];
        $titles = [];
        $frozen = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $title = isset($group['title']) ? (string) $group['title'] : '';
            if ($title === '' && isset($group['group_id'])) {
                $title = '#' . (int) $group['group_id'];
            }
            if ($title !== '') {
                $titles[] = $title;
            }
            $parts = [];
            if (!empty($group['freeze_sale'])) {
                $parts[] = self::l($lex, 'mail_freeze_sale');
            }
            if (!empty($group['freeze_buy'])) {
                $parts[] = self::l($lex, 'mail_freeze_buy');
            }
            if ($parts) {
                $frozen[] = $title . ': ' . implode(', ', $parts);
            }
        }

        $rows[] = ['label' => self::l($lex, 'mail_label_groups'), 'value' => $titles ? implode(', ', $titles) : '—'];
        $rows[] = [
            'label' => self::l($lex, 'mail_label_frozen'),
            'value' => $frozen ? implode('; ', $frozen) : ($event === 'storm_off' ? self::l($lex, 'mail_freeze_none') : '—'),
        ];

        return $rows;
    }

    /**
     * @param array<string,string> $lex
     * @param array<string,mixed> $meta
     * @return array<int,array{label:string,value:string}>
     */
    private static function requestRows(array $data, array $lex, array $meta): array
    {
        $mgrUrl = isset($meta['mgr_url']) ? (string) $meta['mgr_url'] : self::s($data, 'mgr_url');
        $rows = [
            ['label' => self::l($lex, 'mail_label_request_id'), 'value' => (string) (int) self::s($data, 'id', '0')],
            ['label' => self::l($lex, 'mail_label_product'), 'value' => self::s($data, 'product', self::s($data, 'product_id'))],
            ['label' => self::l($lex, 'mail_label_price'), 'value' => self::s($data, 'price')],
            ['label' => self::l($lex, 'mail_label_count'), 'value' => self::s($data, 'count')],
            ['label' => self::l($lex, 'mail_label_amount'), 'value' => self::s($data, 'amount')],
            ['label' => self::l($lex, 'mail_label_name'), 'value' => self::s($data, 'name')],
            ['label' => self::l($lex, 'mail_label_phone'), 'value' => self::s($data, 'phone')],
            ['label' => self::l($lex, 'mail_label_email'), 'value' => self::s($data, 'email')],
        ];
        if ($mgrUrl !== '') {
            $rows[] = ['label' => self::l($lex, 'mail_label_mgr_link'), 'value' => $mgrUrl];
        }

        return $rows;
    }

    /**
     * @param array<string,string> $lex
     */
    private static function l(array $lex, string $key): string
    {
        $full = 'goldprice.' . $key;
        if (isset($lex[$full]) && $lex[$full] !== '') {
            return $lex[$full];
        }
        if (isset($lex[$key]) && $lex[$key] !== '') {
            return $lex[$key];
        }

        return $key;
    }

    /**
     * @param array<string,mixed> $data
     */
    private static function s(array $data, string $key, string $default = ''): string
    {
        if (!array_key_exists($key, $data) || $data[$key] === null) {
            return $default;
        }

        return (string) $data[$key];
    }

    public static function esc(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }

    private static function fallbackTemplate(): string
    {
        return '<!DOCTYPE html><html><body style="margin:0;padding:0;background:#ffffff;">'
            . '<table width="600" cellpadding="0" cellspacing="0" border="0" style="width:600px;margin:0 auto;border-collapse:collapse;">'
            . '<tr><td style="padding:16px;border-bottom:3px solid [[+accent]];">'
            . '<img src="[[+logo_url]]" alt="[[+logo_alt]]" height="48" style="height:48px;display:block;border:0;" />'
            . '</td></tr>'
            . '<tr><td style="padding:20px 16px 8px;font-family:Arial,Helvetica,sans-serif;font-size:18px;font-weight:bold;color:#111;">[[+title]]</td></tr>'
            . '<tr><td style="padding:0 16px 20px;"><table width="100%" cellpadding="0" cellspacing="0" border="0" style="border-collapse:collapse;">[[+rows]]</table></td></tr>'
            . '<tr><td style="padding:12px 16px;border-top:1px solid #E5E5E5;font-family:Arial,Helvetica,sans-serif;font-size:12px;color:#888;">[[+site_name]]</td></tr>'
            . '</table></body></html>';
    }
}
