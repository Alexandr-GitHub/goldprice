<?php

/**
 * Send a harmless test notification to the current mgr user (or email property).
 */
class GoldPriceMgrNotifyTestProcessor extends modProcessor
{
    public $permission = 'settings';

    public function process()
    {
        $this->modx->lexicon->load('goldprice:default');

        /** @var GoldPrice|null $gp */
        $gp = !empty($this->modx->goldprice) ? $this->modx->goldprice : null;
        if (!$gp || !$gp->initialize()) {
            return $this->failure($this->modx->lexicon('goldprice.err_init'));
        }

        $email = trim((string) $this->getProperty('email', ''));
        if ($email === '' && $this->modx->user) {
            $profile = $this->modx->user->getOne('Profile');
            if ($profile) {
                $email = trim((string) $profile->get('email'));
            }
        }
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->failure($this->modx->lexicon('goldprice.err_email'));
        }

        $result = (new \GoldPrice\Service\Notifier($this->modx, $gp))->send(
            'mail_test',
            ['time' => date('Y-m-d H:i:s')],
            [$email]
        );

        if (!$result['sent']) {
            return $this->failure($this->modx->lexicon('goldprice.err_mail_test'));
        }

        return $this->success($this->modx->lexicon('goldprice.mail_test_ok', [
            'email' => $email,
        ]), [
            'email' => $email,
            'recipients' => $result['recipients'],
        ]);
    }
}

return 'GoldPriceMgrNotifyTestProcessor';
