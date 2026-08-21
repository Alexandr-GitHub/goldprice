GoldPrice.grid.Log = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-log',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/log/getlist' },
        fields: ['id', 'created_at', 'event', 'user_id', 'message', 'data'],
        paging: true,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'message',
        columns: [{
            header: _('goldprice.log_created'),
            dataIndex: 'created_at',
            width: 140
        }, {
            header: _('goldprice.log_event'),
            dataIndex: 'event',
            width: 150
        }, {
            header: _('goldprice.log_user'),
            dataIndex: 'user_id',
            width: 80
        }, {
            header: _('goldprice.log_message'),
            dataIndex: 'message',
            width: 360
        }],
        tbar: [{
            xtype: 'combo',
            id: 'goldprice-log-event',
            emptyText: _('goldprice.log_event_all'),
            store: new Ext.data.ArrayStore({
                fields: ['value', 'text'],
                data: [
                    ['', _('goldprice.log_event_all')],
                    ['price_recalculate', 'price_recalculate'],
                    ['price_recalculate_error', 'price_recalculate_error'],
                    ['setting_change', 'setting_change'],
                    ['group_update', 'group_update'],
                    ['recipient_create', 'recipient_create'],
                    ['recipient_update', 'recipient_update'],
                    ['recipient_remove', 'recipient_remove'],
                    ['request_status', 'request_status']
                ]
            }),
            displayField: 'text',
            valueField: 'value',
            mode: 'local',
            triggerAction: 'all',
            width: 200,
            listeners: {
                select: { fn: this.applyFilters, scope: this }
            }
        }, {
            xtype: 'datefield',
            id: 'goldprice-log-date-start',
            emptyText: _('goldprice.log_date_start'),
            format: 'Y-m-d',
            width: 120,
            listeners: {
                select: { fn: this.applyFilters, scope: this }
            }
        }, {
            xtype: 'datefield',
            id: 'goldprice-log-date-end',
            emptyText: _('goldprice.log_date_end'),
            format: 'Y-m-d',
            width: 120,
            listeners: {
                select: { fn: this.applyFilters, scope: this }
            }
        }, {
            text: _('goldprice.log_filter'),
            handler: this.applyFilters,
            scope: this
        }, '->', {
            text: _('goldprice.log_export'),
            handler: this.exportCsv,
            scope: this
        }]
    });
    GoldPrice.grid.Log.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Log, MODx.grid.Grid, {
    applyFilters: function () {
        var event = Ext.getCmp('goldprice-log-event');
        var start = Ext.getCmp('goldprice-log-date-start');
        var end = Ext.getCmp('goldprice-log-date-end');
        this.getStore().baseParams.event = event ? (event.getValue() || '') : '';
        this.getStore().baseParams.date_start = start && start.getValue() ? start.getValue().format('Y-m-d') : '';
        this.getStore().baseParams.date_end = end && end.getValue() ? end.getValue().format('Y-m-d') : '';
        this.getBottomToolbar().changePage(1);
    },
    exportCsv: function () {
        this.applyFilters();
        var p = this.getStore().baseParams || {};
        var q = {
            action: 'mgr/log/export',
            HTTP_MODAUTH: MODx.siteId,
            event: p.event || '',
            date_start: p.date_start || '',
            date_end: p.date_end || ''
        };
        window.location = GoldPrice.config.connector_url + '?' + Ext.urlEncode(q);
    }
});
Ext.reg('goldprice-grid-log', GoldPrice.grid.Log);
