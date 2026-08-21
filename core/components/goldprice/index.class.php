<?php
/**
 * Shared manager controller for the goldprice CMP.
 *
 * @package goldprice
 */

abstract class GoldpriceManagerController extends modExtraManagerController
{
    /** @var GoldPrice */
    public $goldprice;

    /**
     * Loaded here because checkPermissions() runs before initialize().
     */
    protected function loadGoldprice()
    {
        if ($this->goldprice) {
            return;
        }
        $corePath = $this->modx->getOption(
            'goldprice.core_path',
            null,
            $this->modx->getOption('core_path') . 'components/goldprice/'
        );
        require_once $corePath . 'goldprice.class.php';
        $this->goldprice = new GoldPrice($this->modx, ['core_path' => $corePath]);
        $this->goldprice->initialize();
    }

    public function initialize()
    {
        $this->loadGoldprice();
        parent::initialize();
    }

    public function getLanguageTopics()
    {
        return ['goldprice:default'];
    }

    public function checkPermissions()
    {
        $this->loadGoldprice();

        return $this->goldprice && $this->goldprice->isMgrAdmin();
    }
}

class IndexManagerController extends GoldpriceManagerController
{
    public static function getDefaultController()
    {
        return 'home';
    }
}
