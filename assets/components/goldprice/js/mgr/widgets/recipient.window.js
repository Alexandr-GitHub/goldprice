GoldPrice.window.Recipient = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        title: _('goldprice.recipient_create'),
        url: GoldPrice.config.connector_url,
        width: 500,
        autoHeight: true,
        fields: [{
            xtype: 'hidden',
            name: 'id'
        }, {
            xtype: 'textfield',
            name: 'email',
            fieldLabel: _('goldprice.recipient_email'),
            anchor: '100%',
            allowBlank: false
        }, {
            xtype: 'textfield',
            name: 'name',
            fieldLabel: _('goldprice.recipient_name'),
            anchor: '100%'
        }, {
            xtype: 'xcheckbox',
            name: 'active',
            boxLabel: _('goldprice.recipient_active'),
            inputValue: 1,
            checked: true
        }, {
            xtype: 'xcheckbox',
            name: 'storm_on',
            boxLabel: _('goldprice.recipient_storm_on'),
            inputValue: 1
        }, {
            xtype: 'xcheckbox',
            name: 'storm_off',
            boxLabel: _('goldprice.recipient_storm_off'),
            inputValue: 1
        }, {
            xtype: 'xcheckbox',
            name: 'daily_limit',
            boxLabel: _('goldprice.recipient_daily_limit'),
            inputValue: 1
        }, {
            xtype: 'xcheckbox',
            name: 'api_error',
            boxLabel: _('goldprice.recipient_api_error'),
            inputValue: 1
        }, {
            xtype: 'xcheckbox',
            name: 'new_request',
            boxLabel: _('goldprice.recipient_new_request'),
            inputValue: 1
        }]
    });
    GoldPrice.window.Recipient.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.window.Recipient, MODx.Window);
Ext.reg('goldprice-window-recipient', GoldPrice.window.Recipient);
