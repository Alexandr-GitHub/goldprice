GoldPrice.window.GroupRoot = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        title: _('goldprice.group_root_create'),
        url: GoldPrice.config.connector_url,
        width: 520,
        autoHeight: true,
        fields: [{
            xtype: 'hidden',
            name: 'id'
        }, {
            xtype: 'textfield',
            name: 'title',
            fieldLabel: _('goldprice.group_title'),
            anchor: '100%',
            allowBlank: false
        }, {
            xtype: 'numberfield',
            name: 'weight',
            fieldLabel: _('goldprice.group_weight'),
            anchor: '100%',
            allowNegative: false,
            decimalPrecision: 4,
            allowBlank: false
        }, {
            xtype: 'numberfield',
            name: 'sale_markup',
            fieldLabel: _('goldprice.group_sale_markup'),
            anchor: '100%',
            decimalPrecision: 4,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'sale_fix',
            fieldLabel: _('goldprice.group_sale_fix'),
            anchor: '100%',
            decimalPrecision: 2,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'buy_discount',
            fieldLabel: _('goldprice.group_buy_discount'),
            anchor: '100%',
            decimalPrecision: 4,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'buy_fix',
            fieldLabel: _('goldprice.group_buy_fix'),
            anchor: '100%',
            decimalPrecision: 2,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'price_step',
            fieldLabel: _('goldprice.group_price_step'),
            anchor: '100%',
            allowNegative: false,
            decimalPrecision: 2,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'stoploss',
            fieldLabel: _('goldprice.group_stoploss'),
            anchor: '100%',
            decimalPrecision: 4,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'min_margin',
            fieldLabel: _('goldprice.group_min_margin'),
            anchor: '100%',
            decimalPrecision: 2,
            value: 0
        }]
    });
    GoldPrice.window.GroupRoot.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.window.GroupRoot, MODx.Window);
Ext.reg('goldprice-window-group-root', GoldPrice.window.GroupRoot);

GoldPrice.window.GroupSubgroup = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        title: _('goldprice.group_subgroup_create'),
        url: GoldPrice.config.connector_url,
        width: 520,
        autoHeight: true,
        fields: [{
            xtype: 'hidden',
            name: 'id'
        }, {
            xtype: 'combo',
            name: 'parent_id',
            hiddenName: 'parent_id',
            fieldLabel: _('goldprice.group_parent'),
            anchor: '100%',
            store: new Ext.data.JsonStore({
                url: GoldPrice.config.connector_url,
                root: 'results',
                totalProperty: 'total',
                fields: ['id', 'title', 'weight', 'parent_id'],
                baseParams: { action: 'mgr/group/getlist', limit: 0 },
                autoLoad: true,
                listeners: {
                    load: function (store) {
                        store.filterBy(function (rec) {
                            return !rec.get('parent_id');
                        });
                    }
                }
            }),
            valueField: 'id',
            displayField: 'title',
            mode: 'remote',
            triggerAction: 'all',
            editable: false,
            forceSelection: true,
            allowBlank: false
        }, {
            xtype: 'textfield',
            name: 'title',
            fieldLabel: _('goldprice.group_title'),
            anchor: '100%',
            allowBlank: false
        }, {
            xtype: 'numberfield',
            name: 'sale_markup',
            fieldLabel: _('goldprice.group_sale_markup'),
            anchor: '100%',
            decimalPrecision: 4,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'sale_fix',
            fieldLabel: _('goldprice.group_sale_fix'),
            anchor: '100%',
            decimalPrecision: 2,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'buy_discount',
            fieldLabel: _('goldprice.group_buy_discount'),
            anchor: '100%',
            decimalPrecision: 4,
            value: 0
        }, {
            xtype: 'numberfield',
            name: 'buy_fix',
            fieldLabel: _('goldprice.group_buy_fix'),
            anchor: '100%',
            decimalPrecision: 2,
            value: 0
        }]
    });
    GoldPrice.window.GroupSubgroup.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.window.GroupSubgroup, MODx.Window);
Ext.reg('goldprice-window-group-subgroup', GoldPrice.window.GroupSubgroup);
