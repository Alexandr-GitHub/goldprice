<?php
class GoldPriceMgrRecipientUpdateProcessor extends modObjectUpdateProcessor
{
    public $classKey = 'GoldPriceRecipient';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.recipient';

    public function beforeSet()
    {
        $email = trim((string) $this->getProperty('email', $this->object->get('email')));
        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->modx->lexicon('goldprice.err_email');
        }
        $this->setProperty('email', $email);
        $this->setProperty('name', trim((string) $this->getProperty('name', '')));

        foreach (['active', 'storm_on', 'storm_off', 'daily_limit', 'api_error', 'new_request'] as $flag) {
            $this->setProperty($flag, empty($this->getProperty($flag)) ? 0 : 1);
        }

        $exists = $this->modx->getObject('GoldPriceRecipient', [
            'email' => $email,
            'id:!=' => (int) $this->object->get('id'),
        ]);
        if ($exists) {
            return $this->modx->lexicon('goldprice.err_email_unique');
        }

        return parent::beforeSet();
    }

    public function afterSave()
    {
        if ($this->modx->goldprice) {
            $this->modx->goldprice->writeLog('recipient_update', $this->object->get('email'), [
                'id' => (int) $this->object->get('id'),
                'email' => $this->object->get('email'),
            ]);
        }

        return parent::afterSave();
    }
}

return 'GoldPriceMgrRecipientUpdateProcessor';
