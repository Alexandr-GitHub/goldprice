GoldPrice.panel.Home = function (config) {
    config = config || {};
    Ext.applyIf(config, {
        id: 'goldprice-panel-home',
        cls: 'container',
        items: [{
            html: '<h2>' + _('goldprice.menu') + '</h2>',
            cls: 'modx-page-header'
        }, {
            xtype: 'modx-tabs',
            id: 'goldprice-home-tabs',
            stateful: true,
            stateId: 'goldprice-home-tabs',
            defaults: {
                autoHeight: true,
                layout: 'anchor'
            },
            items: [{
                title: _('goldprice.tab_quotes'),
                id: 'goldprice-tab-quotes',
                items: [{
                    html: '<p>' + _('goldprice.tab_quotes_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-quotes',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('goldprice.tab_groups'),
                id: 'goldprice-tab-groups',
                items: [{
                    html: '<p>' + _('goldprice.tab_groups_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-groups',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('goldprice.tab_prices'),
                id: 'goldprice-tab-prices',
                items: [{
                    html: '<p>' + _('goldprice.tab_prices_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-prices',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('goldprice.tab_settings'),
                id: 'goldprice-tab-settings',
                items: [{
                    html: '<p>' + _('goldprice.tab_settings_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-panel-settings'
                }]
            }, {
                title: _('goldprice.tab_recipients'),
                id: 'goldprice-tab-recipients',
                items: [{
                    html: '<p>' + _('goldprice.tab_recipients_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-recipients',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('goldprice.tab_requests'),
                id: 'goldprice-tab-requests',
                items: [{
                    html: '<p>' + _('goldprice.tab_requests_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-requests',
                    cls: 'main-wrapper'
                }]
            }, {
                title: _('goldprice.tab_log'),
                id: 'goldprice-tab-log',
                items: [{
                    html: '<p>' + _('goldprice.tab_log_intro') + '</p>',
                    cls: 'panel-desc'
                }, {
                    xtype: 'goldprice-grid-log',
                    cls: 'main-wrapper'
                }]
            }]
        }]
    });
    GoldPrice.panel.Home.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice.panel.Home, MODx.Panel);
Ext.reg('goldprice-panel-home', GoldPrice.panel.Home);
