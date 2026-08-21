<?php

use GoldPrice\Mgr\CmpFormat;

class GoldPriceMgrSettingsUpdateProcessor extends modProcessor
{
    public $permission = 'settings';

    public function process()
    {
        $q = $this->modx->newQuery('modSystemSetting');
        $q->where(['namespace' => 'goldprice']);
        /** @var modSystemSetting[] $objects */
        $objects = $this->modx->getCollection('modSystemSetting', $q);
        if (!$objects) {
            return $this->failure($this->modx->lexicon('goldprice.err_settings'));
        }

        $before = [];
        $after = [];
        $changed = [];

        foreach ($objects as $setting) {
            $key = (string) $setting->get('key');
            $short = strpos($key, 'goldprice.') === 0 ? substr($key, 10) : $key;
            // MODX 2.8 modProcessor has no issetProperty().
            if (!array_key_exists($short, $this->getProperties())) {
                continue;
            }

            $old = (string) $setting->get('value');
            $new = (string) $this->getProperty($short);
            if ($setting->get('xtype') === 'numberfield') {
                $number = CmpFormat::sanitizeNumber($new);
                if ($number === null) {
                    return $this->failure($this->modx->lexicon('goldprice.err_setting_number', [
                        'key' => $key,
                    ]));
                }
                $new = (string) $number;
            }

            $before[$key] = $old;
            $after[$key] = $new;
            if ($old === $new) {
                continue;
            }

            $setting->set('value', $new);
            if (!$setting->save()) {
                return $this->failure($this->modx->lexicon('goldprice.err_setting_save', [
                    'key' => $key,
                ]));
            }
            $changed[] = ['key' => $key, 'old' => $old, 'new' => $new];
        }

        $diffs = CmpFormat::settingDiffs($before, $after);
        if ($diffs) {
            $user = $this->modx->user ? (string) $this->modx->user->get('username') : '';
            $lines = [];
            foreach ($diffs as $diff) {
                $lines[] = $diff['key'] . ': "' . $diff['old'] . '" → "' . $diff['new'] . '"';
            }
            $message = $this->modx->lexicon('goldprice.log_setting_change', [
                'user' => $user,
                'when' => date('Y-m-d H:i:s'),
                'changes' => implode('; ', $lines),
            ]);
            if ($this->modx->goldprice) {
                $this->modx->goldprice->writeLog('setting_change', $message, [
                    'user' => $user,
                    'diffs' => $diffs,
                ]);
            }
        }

        $this->modx->reloadConfig();
        if ($this->modx->cacheManager) {
            $this->modx->cacheManager->refresh([
                'system_settings' => [],
            ]);
        }

        return $this->success($this->modx->lexicon('goldprice.settings_saved'), [
            'changed' => count($changed),
        ]);
    }
}

return 'GoldPriceMgrSettingsUpdateProcessor';
