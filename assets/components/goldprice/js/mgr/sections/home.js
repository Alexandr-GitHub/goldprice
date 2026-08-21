GoldPrice.page.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        formpanel: 'goldprice-panel-home',
        buttons: [{
            text: _('goldprice.recalculate'),
            id: 'goldprice-btn-recalculate',
            cls: 'primary-button',
            handler: this.recalculate,
            scope: this
        }],
        components: [{
            xtype: 'goldprice-panel-home'
        }]
    });
    GoldPrice.page.Home.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.page.Home, MODx.Component, {
    recalculate: function () {
        var btn = Ext.getCmp('goldprice-btn-recalculate');
        if (btn) {
            btn.setDisabled(true);
        }
        MODx.Ajax.request({
            url: GoldPrice.config.connector_url,
            params: { action: 'mgr/recalculate' },
            listeners: {
                success: {
                    fn: function (r) {
                        if (btn) {
                            btn.setDisabled(false);
                        }
                        GoldPrice.refreshAfterRecalc();
                        MODx.msg.alert(_('goldprice.recalculate'), r.message || _('goldprice.recalculate_ok'));
                    },
                    scope: this
                },
                failure: {
                    fn: function (r) {
                        if (btn) {
                            btn.setDisabled(false);
                        }
                        MODx.msg.alert(_('error'), (r && r.message) ? r.message : _('goldprice.err_recalculate'));
                    },
                    scope: this
                }
            }
        });
    }
});
Ext.reg('goldprice-page-home', GoldPrice.page.Home);

GoldPrice.refreshAfterRecalc = function () {
    var ids = ['goldprice-grid-prices', 'goldprice-grid-log', 'goldprice-grid-groups'];
    for (var i = 0; i < ids.length; i++) {
        var cmp = Ext.getCmp(ids[i]);
        if (cmp && cmp.refresh) {
            cmp.refresh();
        }
    }
};
