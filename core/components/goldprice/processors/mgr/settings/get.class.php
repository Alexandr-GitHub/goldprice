<?php
class GoldPriceMgrSettingsGetProcessor extends modProcessor
{
    public $permission = 'settings';

    public function process()
    {
        $q = $this->modx->newQuery('modSystemSetting');
        $q->where(['namespace' => 'goldprice']);
        $q->sortby('area', 'ASC');

        $values = [];
        /** @var modSystemSetting[] $settings */
        $settings = $this->modx->getCollection('modSystemSetting', $q);
        foreach ($settings as $setting) {
            $key = (string) $setting->get('key');
            $short = strpos($key, 'goldprice.') === 0 ? substr($key, 10) : $key;
            $values[$short] = (string) $setting->get('value');
        }

        return $this->success('', $values);
    }
}

return 'GoldPriceMgrSettingsGetProcessor';
