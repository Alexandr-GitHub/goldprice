var GoldPrice = function (config) {
    config = config || {};
    GoldPrice.superclass.constructor.call(this, config);
};
Ext.extend(GoldPrice, Ext.Component, {
    page: {}, window: {}, grid: {}, panel: {}, combo: {}, config: {}
});
Ext.reg('goldprice', GoldPrice);
GoldPrice = new GoldPrice();

GoldPrice.booleanRenderer = function (value) {
    return value ? _('yes') : _('no');
};
