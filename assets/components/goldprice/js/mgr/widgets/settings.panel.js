GoldPrice.panel.Settings = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-panel-settings',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/settings/update' },
        cls: 'main-wrapper goldprice-settings-panel',
        layout: 'form',
        labelAlign: 'top',
        defaults: {
            anchor: '100%',
            msgTarget: 'under'
        },
        items: [{
            xtype: 'fieldset',
            title: _('area_goldprice.pricing'),
            defaults: { anchor: '100%' },
            items: [
                GoldPrice.settingField('vat_pct', _('setting_goldprice.vat_pct'), _('setting_goldprice.vat_pct_desc'), true),
                GoldPrice.settingField('weight_tolerance', _('setting_goldprice.weight_tolerance'), _('setting_goldprice.weight_tolerance_desc'), true),
                GoldPrice.settingField('recalc_lock_ttl', _('setting_goldprice.recalc_lock_ttl'), _('setting_goldprice.recalc_lock_ttl_desc'), true)
            ]
        }, {
            xtype: 'fieldset',
            title: _('area_goldprice.storm'),
            defaults: { anchor: '100%' },
            items: [
                GoldPrice.settingField('storm_window', _('setting_goldprice.storm_window'), _('setting_goldprice.storm_window_desc'), true),
                GoldPrice.settingField('storm_duration', _('setting_goldprice.storm_duration'), _('setting_goldprice.storm_duration_desc'), true)
            ]
        }, {
            xtype: 'fieldset',
            title: _('area_goldprice.api'),
            defaults: { anchor: '100%' },
            items: [
                GoldPrice.settingField('quote_max_age', _('setting_goldprice.quote_max_age'), _('setting_goldprice.quote_max_age_desc'), true),
                GoldPrice.settingField('usd_max_age', _('setting_goldprice.usd_max_age'), _('setting_goldprice.usd_max_age_desc'), true),
                GoldPrice.settingField('pf_url', _('setting_goldprice.pf_url'), _('setting_goldprice.pf_url_desc'), false),
                GoldPrice.settingField('pf_sid', _('setting_goldprice.pf_sid'), _('setting_goldprice.pf_sid_desc'), false, { inputType: 'password' }),
                GoldPrice.settingField('pf_tickers', _('setting_goldprice.pf_tickers'), _('setting_goldprice.pf_tickers_desc'), false),
                GoldPrice.settingField('pf_bind_ip', _('setting_goldprice.pf_bind_ip'), _('setting_goldprice.pf_bind_ip_desc'), false),
                GoldPrice.settingField('pf_timeout', _('setting_goldprice.pf_timeout'), _('setting_goldprice.pf_timeout_desc'), true)
            ]
        }, {
            xtype: 'fieldset',
            title: _('area_goldprice.buyout'),
            defaults: { anchor: '100%' },
            items: [
                GoldPrice.settingField('daily_buyout_limit', _('setting_goldprice.daily_buyout_limit'), _('setting_goldprice.daily_buyout_limit_desc'), true),
                GoldPrice.settingField('deal_buyout_limit', _('setting_goldprice.deal_buyout_limit'), _('setting_goldprice.deal_buyout_limit_desc'), true)
            ]
        }, {
            xtype: 'fieldset',
            title: _('area_goldprice.mail'),
            defaults: { anchor: '100%' },
            items: [
                GoldPrice.settingField('mail_from', _('setting_goldprice.mail_from'), _('setting_goldprice.mail_from_desc'), false),
                GoldPrice.settingField('mail_logo_url', _('setting_goldprice.mail_logo_url'), _('setting_goldprice.mail_logo_url_desc'), false)
            ]
        }],
        buttonAlign: 'left',
        buttons: [{
            text: _('save'),
            cls: 'primary-button',
            handler: this.submit,
            scope: this
        }],
        listeners: {
            afterrender: { fn: this.loadValues, scope: this },
            success: {
                fn: function (o) {
                    var msg = (o && o.result && o.result.message) ? o.result.message : _('goldprice.settings_saved');
                    MODx.msg.status({ title: _('success'), message: msg });
                    var log = Ext.getCmp('goldprice-grid-log');
                    if (log && log.refresh) {
                        log.refresh();
                    }
                },
                scope: this
            }
        }
    });
    GoldPrice.panel.Settings.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.panel.Settings, MODx.FormPanel, {
    loadValues: function () {
        MODx.Ajax.request({
            url: GoldPrice.config.connector_url,
            params: { action: 'mgr/settings/get' },
            listeners: {
                success: {
                    fn: function (r) {
                        if (r.object) {
                            this.getForm().setValues(r.object);
                        }
                    },
                    scope: this
                }
            }
        });
    }
});
Ext.reg('goldprice-panel-settings', GoldPrice.panel.Settings);

GoldPrice.settingField = function (name, label, description, numeric, extra) {
    return Ext.apply({
        xtype: numeric ? 'numberfield' : 'textfield',
        name: name,
        id: 'goldprice-setting-' + name,
        fieldLabel: label,
        description: description || '',
        allowDecimals: true,
        allowNegative: false
    }, extra || {});
};
