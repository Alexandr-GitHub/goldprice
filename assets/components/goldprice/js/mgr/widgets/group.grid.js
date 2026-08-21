GoldPrice.grid.Groups = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-groups',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/group/getlist' },
        fields: [
            'id', 'weight', 'title', 'sale_markup', 'sale_fix',
            'buy_discount', 'buy_fix', 'price_step', 'stoploss', 'min_margin'
        ],
        autosave: true,
        save_action: 'mgr/group/updatefromgrid',
        paging: false,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'title',
        columns: [{
            header: _('goldprice.group_weight'),
            dataIndex: 'weight',
            width: 90,
            editor: { xtype: 'numberfield', allowNegative: false, decimalPrecision: 4 }
        }, {
            header: _('goldprice.group_title'),
            dataIndex: 'title',
            width: 140,
            editor: { xtype: 'textfield', allowBlank: false }
        }, {
            header: _('goldprice.group_sale_markup'),
            dataIndex: 'sale_markup',
            width: 110,
            editor: { xtype: 'numberfield', decimalPrecision: 4 }
        }, {
            header: _('goldprice.group_sale_fix'),
            dataIndex: 'sale_fix',
            width: 110,
            editor: { xtype: 'numberfield', decimalPrecision: 2 }
        }, {
            header: _('goldprice.group_buy_discount'),
            dataIndex: 'buy_discount',
            width: 110,
            editor: { xtype: 'numberfield', decimalPrecision: 4 }
        }, {
            header: _('goldprice.group_buy_fix'),
            dataIndex: 'buy_fix',
            width: 110,
            editor: { xtype: 'numberfield', decimalPrecision: 2 }
        }, {
            header: _('goldprice.group_price_step'),
            dataIndex: 'price_step',
            width: 110,
            editor: { xtype: 'numberfield', allowNegative: false, decimalPrecision: 2 }
        }, {
            header: _('goldprice.group_stoploss'),
            dataIndex: 'stoploss',
            width: 100,
            editor: { xtype: 'numberfield', decimalPrecision: 4 }
        }, {
            header: _('goldprice.group_min_margin'),
            dataIndex: 'min_margin',
            width: 110,
            editor: { xtype: 'numberfield', decimalPrecision: 2 }
        }]
    });
    GoldPrice.grid.Groups.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Groups, MODx.grid.Grid, {
    getMenu: function () {
        return [];
    },
    saveRecord: function (e) {
        e.record.set(e.field, e.value);
        MODx.Ajax.request({
            url: this.config.url,
            params: {
                action: this.config.save_action,
                data: Ext.encode(e.record.data)
            },
            listeners: {
                success: {
                    fn: function (r) {
                        e.record.commit();
                        GoldPrice.refreshAfterRecalc();
                        MODx.msg.alert(_('goldprice.recalculate'), r.message || _('goldprice.saved'));
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        e.record.reject();
                        MODx.msg.alert(_('error'), (r && r.message) ? r.message : _('goldprice.err_save'));
                    },
                    scope: this
                }
            }
        });
    }
});
Ext.reg('goldprice-grid-groups', GoldPrice.grid.Groups);
