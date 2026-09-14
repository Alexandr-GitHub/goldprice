GoldPrice.grid.GroupTrash = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-group-trash',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/group/getlist', trash: 1 },
        fields: [
            'id', 'parent_id', 'parent_title', 'level', 'weight', 'title', 'deleted_at'
        ],
        paging: false,
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
            width: 90
        }, {
            header: _('goldprice.group_title'),
            dataIndex: 'title',
            width: 200,
            renderer: function (value, meta, record) {
                if (record.get('level') > 0) {
                    return '&nbsp;&nbsp;&nbsp;' + Ext.util.Format.htmlEncode(value);
                }
                return value;
            }
        }, {
            header: _('goldprice.group_deleted_at'),
            dataIndex: 'deleted_at',
            width: 150
        }],
        tbar: []
    });
    GoldPrice.grid.GroupTrash.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.GroupTrash, MODx.grid.Grid, {
    getMenu: function () {
        if (!this.menu.record) {
            return [];
        }
        return [{
            text: _('goldprice.group_restore'),
            handler: this.restoreGroup
        }, {
            text: _('goldprice.group_purge'),
            handler: this.purgeGroup
        }];
    },
    restoreGroup: function () {
        MODx.msg.confirm({
            title: _('goldprice.group_restore'),
            text: _('goldprice.group_restore_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/group/restore',
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
    purgeGroup: function () {
        var isSubgroup = !!this.menu.record.get('parent_id');
        MODx.msg.confirm({
            title: _('goldprice.group_purge'),
            text: isSubgroup
                ? _('goldprice.group_subgroup_purge_confirm')
                : _('goldprice.group_root_purge_confirm'),
            url: this.config.url,
            params: {
                action: 'mgr/group/purge',
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
    }
});
Ext.reg('goldprice-grid-group-trash', GoldPrice.grid.GroupTrash);
