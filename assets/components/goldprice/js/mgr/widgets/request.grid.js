GoldPrice.grid.Requests = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-requests',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/request/getlist' },
        fields: [
            'id', 'created_at', 'product_id', 'product_name', 'price', 'count', 'amount',
            'name', 'phone', 'email', 'comment', 'status', 'manager_id'
        ],
        autosave: true,
        save_action: 'mgr/request/updatefromgrid',
        paging: true,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'name',
        columns: [{
            header: _('goldprice.request_created'),
            dataIndex: 'created_at',
            width: 140
        }, {
            header: _('goldprice.request_product'),
            dataIndex: 'product_name',
            width: 160
        }, {
            header: _('goldprice.request_price'),
            dataIndex: 'price',
            width: 90
        }, {
            header: _('goldprice.request_count'),
            dataIndex: 'count',
            width: 70
        }, {
            header: _('goldprice.request_amount'),
            dataIndex: 'amount',
            width: 90
        }, {
            header: _('goldprice.request_name'),
            dataIndex: 'name',
            width: 140
        }, {
            header: _('goldprice.request_phone'),
            dataIndex: 'phone',
            width: 120
        }, {
            header: _('goldprice.request_email'),
            dataIndex: 'email',
            width: 160
        }, {
            header: _('goldprice.request_comment'),
            dataIndex: 'comment',
            width: 160
        }, {
            header: _('goldprice.request_status'),
            dataIndex: 'status',
            width: 120,
            renderer: GoldPrice.requestStatusRenderer,
            editor: {
                xtype: 'combo',
                store: new Ext.data.ArrayStore({
                    fields: ['value', 'text'],
                    data: GoldPrice.requestStatuses()
                }),
                displayField: 'text',
                valueField: 'value',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                forceSelection: true
            }
        }],
        tbar: [{
            xtype: 'combo',
            id: 'goldprice-requests-status-filter',
            emptyText: _('goldprice.request_status_all'),
            store: new Ext.data.ArrayStore({
                fields: ['value', 'text'],
                data: [['', _('goldprice.request_status_all')]].concat(GoldPrice.requestStatuses())
            }),
            displayField: 'text',
            valueField: 'value',
            mode: 'local',
            triggerAction: 'all',
            editable: false,
            width: 180,
            listeners: {
                select: {
                    fn: function (combo, rec) {
                        this.getStore().baseParams.status = rec.get('value');
                        this.getBottomToolbar().changePage(1);
                    },
                    scope: this
                }
            }
        }]
    });
    GoldPrice.grid.Requests.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Requests, MODx.grid.Grid, {
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
                    fn: function () {
                        e.record.commit();
                        this.refresh();
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
Ext.reg('goldprice-grid-requests', GoldPrice.grid.Requests);

GoldPrice.requestStatuses = function () {
    return [
        ['new', _('goldprice.request_status_new')],
        ['processing', _('goldprice.request_status_processing')],
        ['done', _('goldprice.request_status_done')],
        ['cancelled', _('goldprice.request_status_cancelled')]
    ];
};

GoldPrice.requestStatusRenderer = function (value) {
    var rows = GoldPrice.requestStatuses();
    for (var i = 0; i < rows.length; i++) {
        if (rows[i][0] === value) {
            return rows[i][1];
        }
    }
    return value;
};
