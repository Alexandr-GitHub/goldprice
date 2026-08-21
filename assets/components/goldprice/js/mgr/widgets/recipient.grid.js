GoldPrice.grid.Recipients = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-recipients',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/recipient/getlist' },
        fields: [
            'id', 'email', 'name', 'active', 'storm_on', 'storm_off',
            'daily_limit', 'api_error', 'new_request'
        ],
        paging: true,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'email',
        columns: [{
            header: _('goldprice.recipient_email'),
            dataIndex: 'email',
            width: 180
        }, {
            header: _('goldprice.recipient_name'),
            dataIndex: 'name',
            width: 140
        }, {
            header: _('goldprice.recipient_active'),
            dataIndex: 'active',
            width: 80,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.recipient_storm_on'),
            dataIndex: 'storm_on',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.recipient_storm_off'),
            dataIndex: 'storm_off',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.recipient_daily_limit'),
            dataIndex: 'daily_limit',
            width: 100,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.recipient_api_error'),
            dataIndex: 'api_error',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.recipient_new_request'),
            dataIndex: 'new_request',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }],
        tbar: [{
            text: _('goldprice.recipient_create'),
            cls: 'primary-button',
            handler: this.createRecipient,
            scope: this
        }, {
            text: _('goldprice.mail_test'),
            handler: this.sendTestMail,
            scope: this
        }]
    });
    GoldPrice.grid.Recipients.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Recipients, MODx.grid.Grid, {
    getMenu: function () {
        return [{
            text: _('update'),
            handler: this.updateRecipient
        }, '-', {
            text: _('delete'),
            handler: this.removeRecipient
        }];
    },
    sendTestMail: function () {
        MODx.Ajax.request({
            url: this.config.url,
            params: { action: 'mgr/notify/test' },
            listeners: {
                success: {
                    fn: function (r) {
                        var msg = (r && r.message) ? r.message : _('goldprice.mail_test_ok');
                        MODx.msg.status({ title: _('success'), message: msg });
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        var msg = (r && r.message) ? r.message : _('goldprice.err_mail_test');
                        MODx.msg.alert(_('error'), msg);
                    },
                    scope: this
                }
            }
        });
    },
    createRecipient: function () {
        var w = MODx.load({
            xtype: 'goldprice-window-recipient',
            title: _('goldprice.recipient_create'),
            baseParams: { action: 'mgr/recipient/create' },
            listeners: {
                success: { fn: this.refresh, scope: this }
            }
        });
        w.reset();
        w.setValues({ active: 1 });
        w.show();
    },
    updateRecipient: function () {
        var rec = this.menu.record;
        var w = MODx.load({
            xtype: 'goldprice-window-recipient',
            title: _('goldprice.recipient_update'),
            baseParams: { action: 'mgr/recipient/update' },
            listeners: {
                success: { fn: this.refresh, scope: this }
            }
        });
        w.reset();
        w.setValues(rec);
        w.show();
    },
    removeRecipient: function () {
        MODx.msg.confirm({
            title: _('delete'),
            text: _('goldprice.recipient_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/recipient/remove',
                id: this.menu.record.id
            },
            listeners: {
                success: { fn: this.refresh, scope: this }
            }
        });
    }
});
Ext.reg('goldprice-grid-recipients', GoldPrice.grid.Recipients);
