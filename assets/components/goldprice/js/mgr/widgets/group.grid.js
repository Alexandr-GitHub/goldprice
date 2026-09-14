GoldPrice.grid.Groups = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-groups',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/group/getlist' },
        fields: [
            'id', 'parent_id', 'parent_title', 'level', 'weight', 'title',
            'sale_markup', 'sale_fix', 'buy_discount', 'buy_fix',
            'price_step', 'stoploss', 'min_margin', 'add_to_parent'
        ],
        autosave: true,
        save_action: 'mgr/group/updatefromgrid',
        paging: false,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'title',
        columns: [{
            header: _('goldprice.group_parent'),
            dataIndex: 'parent_title',
            width: 120,
            renderer: function (value) {
                return value ? value : '—';
            }
        }, {
            header: _('goldprice.group_weight'),
            dataIndex: 'weight',
            width: 90,
            editor: { xtype: 'numberfield', allowNegative: false, decimalPrecision: 4 }
        }, {
            header: _('goldprice.group_title'),
            dataIndex: 'title',
            width: 160,
            renderer: function (value, meta, record) {
                if (record.get('level') > 0) {
                    return '&nbsp;&nbsp;&nbsp;' + Ext.util.Format.htmlEncode(value);
                }
                return value;
            },
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
        }, {
            header: _('goldprice.group_add_to_parent'),
            dataIndex: 'add_to_parent',
            width: 90,
            renderer: function (value, meta, record) {
                if (!record.get('parent_id')) {
                    return '—';
                }
                return value ? '✓' : '';
            },
            editor: { xtype: 'xcheckbox', inputValue: 1 }
        }],
        tbar: [{
            text: _('goldprice.group_root_create'),
            cls: 'primary-button',
            handler: this.createRoot,
            scope: this
        }, '-', {
            text: _('goldprice.group_subgroup_create'),
            handler: this.createSubgroup,
            scope: this
        }]
    });
    GoldPrice.grid.Groups.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Groups, MODx.grid.Grid, {
    subgroupLockedFields: ['weight', 'price_step', 'stoploss'],
    getMenu: function () {
        if (!this.menu.record) {
            return [];
        }
        return [{
            text: _('update'),
            handler: this.updateGroup
        }, {
            text: _('goldprice.group_copy'),
            handler: this.copyGroup
        }, '-', {
            text: _('goldprice.group_remove'),
            handler: this.removeGroup
        }];
    },
    beforeedit: function (e) {
        if (!e.record.get('parent_id')) {
            if (e.field === 'add_to_parent') {
                return false;
            }
            return GoldPrice.grid.Groups.superclass.beforeedit.call(this, e);
        }
        if (e.field === 'add_to_parent') {
            return GoldPrice.grid.Groups.superclass.beforeedit.call(this, e);
        }
        var locked = this.subgroupLockedFields.slice();
        if (e.record.get('add_to_parent')) {
            locked.push('min_margin');
        }
        if (e.field && locked.indexOf(e.field) !== -1) {
            return false;
        }
        return GoldPrice.grid.Groups.superclass.beforeedit.call(this, e);
    },
    createRoot: function () {
        var w = MODx.load({
            xtype: 'goldprice-window-group-root',
            baseParams: { action: 'mgr/group/create' },
            listeners: {
                success: { fn: this.refresh, scope: this }
            }
        });
        w.reset();
        w.show();
    },
    createSubgroup: function () {
        var w = MODx.load({
            xtype: 'goldprice-window-group-subgroup',
            baseParams: { action: 'mgr/group/create' },
            listeners: {
                success: { fn: this.refresh, scope: this }
            }
        });
        w.reset();
        w.show();
    },
    updateGroup: function () {
        var rec = this.menu.record;
        var isSubgroup = !!rec.parent_id;
        var w = MODx.load({
            xtype: isSubgroup ? 'goldprice-window-group-subgroup' : 'goldprice-window-group-root',
            title: isSubgroup ? _('goldprice.group_subgroup_update') : _('goldprice.group_root_update'),
            baseParams: { action: 'mgr/group/update' },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                        GoldPrice.refreshAfterRecalc(r);
                    },
                    scope: this
                }
            }
        });
        w.reset();
        w.setValues(rec);
        w.show();
    },
    copyGroup: function () {
        var rec = this.menu.record;
        var isSubgroup = !!rec.parent_id;
        var values = Ext.apply({}, rec);
        delete values.id;
        values.title = (values.title || '') + _('goldprice.group_copy_suffix');
        var w = MODx.load({
            xtype: isSubgroup ? 'goldprice-window-group-subgroup' : 'goldprice-window-group-root',
            title: isSubgroup ? _('goldprice.group_subgroup_create') : _('goldprice.group_root_create'),
            baseParams: { action: 'mgr/group/create' },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                        GoldPrice.refreshAfterRecalc(r);
                    },
                    scope: this
                }
            }
        });
        w.reset();
        w.setValues(values);
        w.show();
    },
    removeGroup: function () {
        var isSubgroup = !!this.menu.record.parent_id;
        MODx.msg.confirm({
            title: _('goldprice.group_remove'),
            text: isSubgroup
                ? _('goldprice.group_subgroup_remove_confirm')
                : _('goldprice.group_root_remove_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/group/remove',
                id: this.menu.record.id
            },
            listeners: {
                success: {
                    fn: function (r) {
                        this.refresh();
                        GoldPrice.refreshAfterRecalc(r);
                    },
                    scope: this
                }
            }
        });
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
                        GoldPrice.refreshAfterRecalc(r);
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
