<?php
class GoldPriceMgrRecipientCreateProcessor extends modObjectCreateProcessor
{
    public $classKey = 'GoldPriceRecipient';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.recipient';

    public function beforeSet()
    {
        if (!$this->getProperty('id')) {
            $this->unsetProperty('id');
        }

        $email = trim((string) $this->getProperty('email', ''));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->modx->lexicon('goldprice.err_email');
        }
        $this->setProperty('email', $email);
        $this->setProperty('name', trim((string) $this->getProperty('name', '')));

        foreach (['active', 'storm_on', 'storm_off', 'daily_limit', 'api_error', 'new_request'] as $flag) {
            $this->setProperty($flag, empty($this->getProperty($flag)) ? 0 : 1);
        }

        $exists = $this->modx->getObject('GoldPriceRecipient', ['email' => $email]);
        if ($exists) {
            return $this->modx->lexicon('goldprice.err_email_unique');
        }

        return parent::beforeSet();
    }

    public function afterSave()
    {
        if ($this->modx->goldprice) {
            $this->modx->goldprice->writeLog('recipient_create', $this->object->get('email'), [
                'id' => (int) $this->object->get('id'),
                'email' => $this->object->get('email'),
            ]);
        }

        return parent::afterSave();
    }
}

return 'GoldPriceMgrRecipientCreateProcessor';
