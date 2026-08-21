GoldPrice.grid.Quotes = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-grid-quotes',
        url: GoldPrice.config.connector_url,
        baseParams: { action: 'mgr/quote/getlist' },
        fields: [
            'id', 'created_at', 'gold', 'gold_delta', 'xau_usd', 'usd_rub',
            'usd_delta', 'bid', 'ask', 'netchange_pct', 'source'
        ],
        paging: true,
        pageSize: 20,
        remoteSort: true,
        autoExpandColumn: 'source',
        columns: [{
            header: _('goldprice.quote_created'),
            dataIndex: 'created_at',
            width: 140
        }, {
            header: _('goldprice.quote_gold'),
            dataIndex: 'gold',
            width: 100
        }, {
            header: _('goldprice.quote_gold_delta'),
            dataIndex: 'gold_delta',
            width: 90
        }, {
            header: _('goldprice.quote_xau_usd'),
            dataIndex: 'xau_usd',
            width: 90
        }, {
            header: _('goldprice.quote_usd_rub'),
            dataIndex: 'usd_rub',
            width: 90
        }, {
            header: _('goldprice.quote_usd_delta'),
            dataIndex: 'usd_delta',
            width: 90
        }, {
            header: _('goldprice.quote_bid'),
            dataIndex: 'bid',
            width: 80
        }, {
            header: _('goldprice.quote_ask'),
            dataIndex: 'ask',
            width: 80
        }, {
            header: _('goldprice.quote_netchange_pct'),
            dataIndex: 'netchange_pct',
            width: 100
        }, {
            header: _('goldprice.quote_source'),
            dataIndex: 'source',
            width: 100
        }]
    });
    GoldPrice.grid.Quotes.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.grid.Quotes, MODx.grid.Grid);
Ext.reg('goldprice-grid-quotes', GoldPrice.grid.Quotes);
