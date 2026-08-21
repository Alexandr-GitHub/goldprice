GoldPrice.grid.Prices = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-prices',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/price/getlist' },
        fields: [
            'id', 'product_id', 'product_name', 'product_weight', 'group_id', 'group_title',
            'cost', 'sale_price', 'buy_price', 'buy_price_display',
            'sale_frozen', 'buy_frozen', 'updated_at'
        ],
        paging: true,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'product_name',
        columns: [{
            header: _('goldprice.price_product'),
            dataIndex: 'product_name',
            width: 220
        }, {
            header: _('goldprice.price_weight'),
            dataIndex: 'product_weight',
            width: 80
        }, {
            header: _('goldprice.price_group'),
            dataIndex: 'group_title',
            width: 120
        }, {
            header: _('goldprice.price_cost'),
            dataIndex: 'cost',
            width: 100
        }, {
            header: _('goldprice.price_sale'),
            dataIndex: 'sale_price',
            width: 100
        }, {
            header: _('goldprice.price_buy'),
            dataIndex: 'buy_price_display',
            width: 140
        }, {
            header: _('goldprice.price_sale_frozen'),
            dataIndex: 'sale_frozen',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.price_buy_frozen'),
            dataIndex: 'buy_frozen',
            width: 90,
            renderer: GoldPrice.booleanRenderer
        }, {
            header: _('goldprice.price_updated'),
            dataIndex: 'updated_at',
            width: 140
        }],
        tbar: ['->', {
            xtype: 'textfield',
            id: 'goldprice-prices-search',
            emptyText: _('goldprice.search'),
            listeners: {
                specialkey: {
                    fn: function (field, e) {
                        if (e.getKey() === Ext.EventObject.ENTER) {
                            this.search(field.getValue());
                        }
                    },
                    scope: this
                }
            }
        }, {
            text: _('goldprice.search'),
            handler: function () {
                var field = Ext.getCmp('goldprice-prices-search');
                this.search(field ? field.getValue() : '');
            },
            scope: this
        }]
    });
    GoldPrice.grid.Prices.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Prices, MODx.grid.Grid, {
    search: function (query) {
        this.getStore().baseParams.query = query || '';
        this.getBottomToolbar().changePage(1);
    }
});
Ext.reg('goldprice-grid-prices', GoldPrice.grid.Prices);
