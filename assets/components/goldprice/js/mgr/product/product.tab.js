Ext.override(miniShop2.panel.Product, {

    goldpriceOriginals: {
        getFields: miniShop2.panel.Product.prototype.getFields
    },

    getFields: function (config) {
        var fields = this.goldpriceOriginals.getFields.call(this, config);
        var data = (GoldPrice.config && GoldPrice.config.product) ? GoldPrice.config.product : {};
        var groups = (GoldPrice.config && GoldPrice.config.groups) ? GoldPrice.config.groups : [];

        var groupData = [['0', _('goldprice.group_auto')]];
        for (var i = 0; i < groups.length; i++) {
            groupData.push([String(groups[i].id), groups[i].title + ' (' + groups[i].weight + ' г)']);
        }

        var metalData = [
            ['', '—'],
            ['золото', _('goldprice.metal_gold')],
            ['серебро', _('goldprice.metal_silver')]
        ];
        var coinData = [
            ['', '—'],
            ['инвестиционные', _('goldprice.coin_investment')],
            ['памятные', _('goldprice.coin_commemorative')]
        ];

        var groupId = data.group_id ? String(data.group_id) : '0';

        var mkCombo = function (name, id, label, storeData, value, description) {
            return {
                xtype: 'combo',
                name: name,
                hiddenName: name,
                id: id,
                fieldLabel: label,
                description: description || '',
                store: new Ext.data.ArrayStore({
                    id: 0,
                    fields: ['value', 'text'],
                    data: storeData
                }),
                valueField: 'value',
                displayField: 'text',
                mode: 'local',
                triggerAction: 'all',
                editable: false,
                forceSelection: true,
                typeAhead: false,
                value: value
            };
        };

        for (var fi in fields) {
            if (!fields.hasOwnProperty(fi)) {
                continue;
            }
            var item = fields[fi];
            if (item.id !== 'modx-resource-tabs') {
                continue;
            }
            for (var ti in item.items) {
                if (!item.items.hasOwnProperty(ti)) {
                    continue;
                }
                var tab = item.items[ti];
                if (tab.id !== 'minishop2-product-tab' || !tab.items || !tab.items[0]) {
                    continue;
                }
                tab.items[0].items.push({
                    title: _('goldprice.tab'),
                    id: 'goldprice-product-tab',
                    hideMode: 'offsets',
                    autoScroll: true,
                    items: [{
                        xtype: 'panel',
                        layout: 'form',
                        labelAlign: 'top',
                        bodyCssClass: 'main-wrapper',
                        cls: 'goldprice-product-panel',
                        defaults: {
                            anchor: '100%',
                            msgTarget: 'under'
                        },
                        items: [{
                            xtype: 'numberfield',
                            name: 'goldprice[weight]',
                            id: 'goldprice-weight',
                            fieldLabel: _('goldprice.field_weight'),
                            description: _('goldprice.field_weight_help'),
                            allowNegative: false,
                            allowDecimals: true,
                            decimalPrecision: 4,
                            value: data.weight || 0
                        },
                        mkCombo('goldprice[metal]', 'goldprice-metal', _('goldprice.field_metal'), metalData, data.metal || ''),
                        mkCombo('goldprice[coin_type]', 'goldprice-coin-type', _('goldprice.field_coin_type'), coinData, data.coin_type || ''),
                        mkCombo('goldprice[group_id]', 'goldprice-group-id', _('goldprice.field_group'), groupData, groupId, _('goldprice.field_group_help')),
                        {
                            xtype: 'fieldset',
                            title: _('goldprice.fieldset_custom'),
                            collapsible: false,
                            autoHeight: true,
                            defaults: {
                                anchor: '100%',
                                msgTarget: 'under'
                            },
                            items: [{
                                xtype: 'xcheckbox',
                                name: 'goldprice[use_custom]',
                                id: 'goldprice-use-custom',
                                boxLabel: _('goldprice.field_use_custom'),
                                inputValue: 1,
                                checked: !!data.use_custom
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[custom_pct]',
                                id: 'goldprice-custom-pct',
                                fieldLabel: _('goldprice.field_custom_pct'),
                                description: _('goldprice.field_custom_pct_help'),
                                allowDecimals: true,
                                decimalPrecision: 4,
                                value: data.custom_pct || 0
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[custom_fix]',
                                id: 'goldprice-custom-fix',
                                fieldLabel: _('goldprice.field_custom_fix'),
                                allowNegative: false,
                                allowDecimals: true,
                                decimalPrecision: 2,
                                value: data.custom_fix || 0
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[custom_buy_pct]',
                                id: 'goldprice-custom-buy-pct',
                                fieldLabel: _('goldprice.field_custom_buy_pct'),
                                description: _('goldprice.field_custom_buy_pct_help'),
                                allowDecimals: true,
                                decimalPrecision: 4,
                                value: data.custom_buy_pct || 0
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[custom_buy_fix]',
                                id: 'goldprice-custom-buy-fix',
                                fieldLabel: _('goldprice.field_custom_buy_fix'),
                                description: _('goldprice.field_custom_buy_fix_help'),
                                allowNegative: true,
                                allowDecimals: true,
                                decimalPrecision: 2,
                                value: data.custom_buy_fix || 0
                            }]
                        }, {
                            xtype: 'fieldset',
                            title: _('goldprice.fieldset_ignore'),
                            collapsible: false,
                            autoHeight: true,
                            defaults: {
                                anchor: '100%',
                                msgTarget: 'under'
                            },
                            items: [{
                                xtype: 'xcheckbox',
                                name: 'goldprice[ignore_market]',
                                id: 'goldprice-ignore-market',
                                boxLabel: _('goldprice.field_ignore_market'),
                                description: _('goldprice.field_ignore_market_help'),
                                inputValue: 1,
                                checked: !!data.ignore_market
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[fixed_price]',
                                id: 'goldprice-fixed-price',
                                fieldLabel: _('goldprice.field_fixed_price'),
                                description: _('goldprice.field_fixed_price_help'),
                                allowNegative: false,
                                allowDecimals: true,
                                decimalPrecision: 2,
                                value: data.fixed_price || 0
                            }, {
                                xtype: 'numberfield',
                                name: 'goldprice[buyout_price]',
                                id: 'goldprice-buyout-price',
                                fieldLabel: _('goldprice.field_buyout_price'),
                                description: _('goldprice.field_buyout_price_help'),
                                allowNegative: false,
                                allowDecimals: true,
                                decimalPrecision: 2,
                                value: data.buyout_price || 0
                            }]
                        }]
                    }]
                });
            }
        }

        return fields;
    }
});
