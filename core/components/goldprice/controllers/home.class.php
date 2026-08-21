<?php
/**
 * GoldPrice CMP home controller.
 *
 * @package goldprice
 */

require_once dirname(__DIR__) . '/index.class.php';

class GoldpriceHomeManagerController extends GoldpriceManagerController
{
    public function process(array $scriptProperties = [])
    {
    }

    public function getPageTitle()
    {
        return $this->modx->lexicon('goldprice.menu');
    }

    public function loadCustomCssJs()
    {
        $config = [
            'connector_url' => $this->goldprice->config['connector_url'],
            'assets_url' => $this->goldprice->config['assets_url'],
        ];

        $this->addCss($this->goldprice->config['css_url'] . 'mgr/main.css');
        $this->addJavascript($this->goldprice->config['js_url'] . 'mgr/goldprice.js');
        $this->addHtml(
            '<script type="text/javascript">GoldPrice.config=' . $this->modx->toJSON($config) . ';</script>'
        );

        $jsUrl = $this->goldprice->config['js_url'] . 'mgr/';
        $this->addLastJavascript($jsUrl . 'widgets/quote.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/group.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/price.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/settings.panel.js');
        $this->addLastJavascript($jsUrl . 'widgets/recipient.window.js');
        $this->addLastJavascript($jsUrl . 'widgets/recipient.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/request.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/log.grid.js');
        $this->addLastJavascript($jsUrl . 'widgets/home.panel.js');
        $this->addLastJavascript($jsUrl . 'sections/home.js');
        $this->addHtml(
            '<script type="text/javascript">Ext.onReady(function () { MODx.add({ xtype: "goldprice-page-home" }); });</script>'
        );
    }

    public function getTemplateFile()
    {
        return '';
    }
}
