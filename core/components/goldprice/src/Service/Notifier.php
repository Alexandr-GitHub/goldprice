<?php
declare(strict_types=1);

namespace GoldPrice\Service;

use GoldPrice;
use GoldPrice\Domain\Notify\NotifyMailBuilder;

/**
 * Email fan-out for GoldPrice::notify.
 * One SMTP message, all active subscribers in To (fewer roundtrips than per-recipient).
 */
final class Notifier
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
     * @param string[] $forceEmails when set, skip recipient table (test mail)
     * @return array{sent:bool,recipients:int,message:string}
     */
    public function send(string $event, array $data = [], array $forceEmails = []): array
    {
        $event = (string) $event;
        if (!array_key_exists($event, NotifyMailBuilder::$eventFlags)) {
            return ['sent' => false, 'recipients' => 0, 'message' => 'unknown event'];
        }

        $emails = $forceEmails !== []
            ? $this->normalizeEmails($forceEmails)
            : $this->subscriberEmails($event);

        if ($emails === []) {
            return ['sent' => false, 'recipients' => 0, 'message' => 'no subscribers'];
        }

        $data = $this->enrichData($event, $data);
        $mail = NotifyMailBuilder::build($event, $data, $this->lexMap(), $this->meta($data));

        if (!$this->dispatch($emails, $mail['subject'], $mail['html'], $mail['text'])) {
            $this->goldprice->writeLog('mail_fail', 'Mail send failed for ' . $event, [
                'event' => $event,
                'recipients' => count($emails),
            ]);

            return ['sent' => false, 'recipients' => count($emails), 'message' => 'mail_fail'];
        }

        return ['sent' => true, 'recipients' => count($emails), 'message' => 'ok'];
    }

    /**
     * @return string[]
     */
    private function subscriberEmails(string $event): array
    {
        $flag = NotifyMailBuilder::$eventFlags[$event];
        if ($flag === '') {
            return [];
        }
        if (!$this->goldprice->initialize()) {
            return [];
        }

        $criteria = [
            'active' => 1,
            $flag => 1,
        ];
        $emails = [];
        foreach ($this->modx->getCollection('GoldPriceRecipient', $criteria) as $row) {
            $email = strtolower(trim((string) $row->get('email')));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $emails[$email] = $email;
            }
        }

        return array_values($emails);
    }

    /**
     * @param string[] $emails
     * @return string[]
     */
    private function normalizeEmails(array $emails): array
    {
        $out = [];
        foreach ($emails as $email) {
            $email = strtolower(trim((string) $email));
            if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $out[$email] = $email;
            }
        }

        return array_values($out);
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function enrichData(string $event, array $data): array
    {
        if ($event === 'request_new') {
            if (empty($data['product']) && !empty($data['product_id'])) {
                $res = $this->modx->getObject('modResource', (int) $data['product_id']);
                if ($res) {
                    $data['product'] = (string) $res->get('pagetitle');
                }
            }
            if (empty($data['mgr_url'])) {
                $data['mgr_url'] = $this->mgrUrl();
            }
        }
        if (($event === 'storm_on' || $event === 'storm_off') && empty($data['time'])) {
            $data['time'] = date('Y-m-d H:i:s');
        }
        if ($event === 'api_error') {
            if (empty($data['time'])) {
                $data['time'] = date('Y-m-d H:i:s');
            }
            if (isset($data['message'])) {
                $data['message'] = self::safeApiMessage((string) $data['message']);
            }
        }
        if ($event === 'mail_test' && empty($data['time'])) {
            $data['time'] = date('Y-m-d H:i:s');
        }

        return $data;
    }

    /**
     * Strip paths / long tokens that might leak SID or filesystem layout.
     */
    public static function safeApiMessage(string $message): string
    {
        $message = preg_replace('#(/[\\w.\\-]+){2,}#u', '[path]', $message);
        $message = preg_replace('/\\b[A-Za-z0-9_\\-]{20,}\\b/', '[redacted]', (string) $message);

        return trim((string) $message);
    }

    /**
     * @return array<string,string>
     */
    private function lexMap(): array
    {
        $this->modx->lexicon->load('goldprice:default');
        $keys = [
            'goldprice.mail_title_storm_on',
            'goldprice.mail_title_storm_off',
            'goldprice.mail_title_daily_limit',
            'goldprice.mail_title_api_error',
            'goldprice.mail_title_request_new',
            'goldprice.mail_title_mail_test',
            'goldprice.mail_test_body',
            'goldprice.mail_label_event',
            'goldprice.mail_label_time',
            'goldprice.mail_label_date',
            'goldprice.mail_label_change_pct',
            'goldprice.mail_label_groups',
            'goldprice.mail_label_frozen',
            'goldprice.mail_label_sum',
            'goldprice.mail_label_limit',
            'goldprice.mail_label_message',
            'goldprice.mail_label_request_id',
            'goldprice.mail_label_product',
            'goldprice.mail_label_price',
            'goldprice.mail_label_count',
            'goldprice.mail_label_amount',
            'goldprice.mail_label_name',
            'goldprice.mail_label_phone',
            'goldprice.mail_label_email',
            'goldprice.mail_label_mgr_link',
            'goldprice.mail_freeze_sale',
            'goldprice.mail_freeze_buy',
            'goldprice.mail_freeze_none',
        ];
        $map = [];
        foreach ($keys as $key) {
            $map[$key] = (string) $this->modx->lexicon($key);
        }

        return $map;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    private function meta(array $data): array
    {
        $siteUrl = rtrim((string) $this->modx->getOption('site_url'), '/') . '/';
        $logo = trim((string) $this->modx->getOption('goldprice.mail_logo_url', null, ''));
        if ($logo === '') {
            $logo = $siteUrl . 'assets/templates/images/logo-mail.png';
        }

        $tplPath = rtrim($this->goldprice->config['core_path'], '/') . '/elements/chunks/email.notify.tpl';
        $template = is_file($tplPath) ? (string) file_get_contents($tplPath) : '';

        return [
            'logo_url' => $logo,
            'logo_alt' => (string) $this->modx->getOption('site_name', null, 'Golden Pearl'),
            'site_name' => (string) $this->modx->getOption('site_name', null, 'Golden Pearl'),
            'mgr_url' => isset($data['mgr_url']) ? (string) $data['mgr_url'] : $this->mgrUrl(),
            'template' => $template,
        ];
    }

    private function mgrUrl(): string
    {
        $siteUrl = rtrim((string) $this->modx->getOption('site_url'), '/') . '/';
        $manager = trim((string) $this->modx->getOption('manager_url', null, 'manager/'), '/');

        return $siteUrl . $manager . '/?a=home&namespace=goldprice';
    }

    /**
     * @param string[] $emails
     */
    private function dispatch(array $emails, string $subject, string $html, string $text): bool
    {
        /** @var \modPHPMailer|null $mail */
        $mail = $this->modx->getService('mail', 'mail.modPHPMailer');
        if (!$mail) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] mail service unavailable');
            return false;
        }

        // Singleton: leftover To/Reply-To from a previous send show up as "addr,addr".
        $mail->reset();

        $from = trim((string) $this->modx->getOption('goldprice.mail_from', null, ''));
        if ($from === '' || !filter_var($from, FILTER_VALIDATE_EMAIL)) {
            $from = (string) $this->modx->getOption('emailsender');
        }
        $fromName = trim((string) $this->modx->getOption('site_name', null, 'Golden Pearl'));
        $fromName = trim(preg_replace('/\s*\[[^\]]*\]\s*/u', ' ', $fromName));
        $fromName = trim(preg_replace('/\s+/u', ' ', $fromName));
        if ($fromName === '') {
            $fromName = 'Golden Pearl';
        }

        $mail->set(\modMail::MAIL_FROM, $from);
        $mail->set(\modMail::MAIL_FROM_NAME, $fromName);
        $mail->set(\modMail::MAIL_SENDER, $from);
        $mail->set(\modMail::MAIL_SUBJECT, $subject);
        $mail->set(\modMail::MAIL_BODY, $html);
        $mail->set(\modMail::MAIL_BODY_TEXT, $text);

        foreach ($emails as $email) {
            $mail->address('to', $email);
        }

        $ok = (bool) $mail->send();
        $mail->reset();

        if (!$ok) {
            $this->modx->log(\modX::LOG_LEVEL_ERROR, '[goldprice] mail send failed');
        }

        return $ok;
    }
}
