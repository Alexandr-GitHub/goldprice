<?php
class GoldPriceMgrRecipientRemoveProcessor extends modObjectRemoveProcessor
{
    public $classKey = 'GoldPriceRecipient';
    public $languageTopics = ['goldprice:default'];
    public $permission = 'settings';
    public $objectType = 'goldprice.recipient';

    public function afterRemove()
    {
        if ($this->modx->goldprice) {
            $this->modx->goldprice->writeLog('recipient_remove', (string) $this->object->get('email'), [
                'id' => (int) $this->object->get('id'),
                'email' => $this->object->get('email'),
            ]);
        }

        return parent::afterRemove();
    }
}

return 'GoldPriceMgrRecipientRemoveProcessor';
