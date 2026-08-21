<?php
/**
 * GoldPrice service — lazy xPDO package loader + mgr product tab.
 */
class GoldPrice
{
    /** @var modX */
    public $modx;

    /** @var array */
    public $config = [];

    /** @var bool */
    protected $initialized = false;

    /** @var bool */
    protected static $autoloadRegistered = false;

    /** @var array|null validated payload pending OnDocFormSave (static: new instance per event) */
    protected static $pendingProductData = null;

    /** @var int|null */
    protected static $pendingProductId = null;

    /**
     * @param modX $modx
     * @param array $config
     */
    public function __construct(modX &$modx, array $config = [])
    {
        $this->modx =& $modx;
        $corePath = $modx->getOption(
            'goldprice.core_path',
            null,
            $modx->getOption('core_path') . 'components/goldprice/'
        );
        $assetsUrl = $modx->getOption(
            'goldprice.assets_url',
            null,
            $modx->getOption('assets_url') . 'components/goldprice/'
        );
        $this->config = array_merge([
            'core_path' => $corePath,
            'model_path' => $corePath . 'model/',
            'processors_path' => $corePath . 'processors/',
            'assets_url' => $assetsUrl,
            'css_url' => $assetsUrl . 'css/',
            'js_url' => $assetsUrl . 'js/',
            'connector_url' => $assetsUrl . 'connector.php',
        ], $config);

        self::registerAutoload($corePath);
    }

    /**
     * PSR-4 for GoldPrice\ without requiring Composer on the server.
     */
    public static function registerAutoload($corePath)
    {
        if (self::$autoloadRegistered) {
            return;
        }
        self::$autoloadRegistered = true;

        $composer = $corePath . 'vendor/autoload.php';
        if (is_file($composer)) {
            require_once $composer;
            return;
        }

        $src = rtrim($corePath, '/') . '/src/';
        spl_autoload_register(static function ($class) use ($src) {
            $prefix = 'GoldPrice\\';
            if (strpos($class, $prefix) !== 0) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
            $file = $src . $relative;
            if (is_file($file)) {
                require $file;
            }
        });
    }

    /**
     * Register xPDO package on first call.
     *
     * @return bool
     */
    public function initialize()
    {
        if ($this->initialized) {
            return true;
        }

        $modelPath = $this->config['model_path'];
        if (!$this->modx->addPackage('goldprice', $modelPath, $this->modx->config['table_prefix'])) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to add xPDO package');
            return false;
        }

        $this->initialized = true;
        return true;
    }

    /**
     * @param array $scriptProperties
     */
    public function onDocFormPrerender(array $scriptProperties)
    {
        $mode = $this->modx->getOption('mode', $scriptProperties, modSystemEvent::MODE_NEW, true);
        if ($mode == modSystemEvent::MODE_NEW) {
            return;
        }

        /** @var modResource|null $resource */
        $resource = $this->modx->getOption('resource', $scriptProperties, null, true);
        if (!$resource || !$this->isMsProduct($resource)) {
            return;
        }

        if (!$this->initialize()) {
            return;
        }

        $this->modx->controller->addLexiconTopic('goldprice:default');
        $this->modx->lexicon->load('goldprice:default');

        $productId = (int) $resource->get('id');
        $config = [
            'assets_url' => $this->config['assets_url'],
            'product' => $this->getProductData($productId),
            'groups' => $this->getGroupsList(),
        ];

        $css = $this->config['css_url'] . 'mgr/main.css';
        $cssFile = rtrim((string) $this->modx->getOption('assets_path'), '/') . '/components/goldprice/css/mgr/main.css';
        if (is_file($cssFile)) {
            $css .= '?v=' . filemtime($cssFile);
        }
        $this->modx->controller->addCss($css);
        $this->modx->controller->addJavascript($this->config['js_url'] . 'mgr/goldprice.js');
        $this->modx->controller->addHtml(
            '<script type="text/javascript">GoldPrice.config=' . $this->modx->toJSON($config) . ';</script>'
        );
        $jsTab = $this->config['js_url'] . 'mgr/product/product.tab.js';
        $jsFile = rtrim((string) $this->modx->getOption('assets_path'), '/') . '/components/goldprice/js/mgr/product/product.tab.js';
        if (is_file($jsFile)) {
            $jsTab .= '?v=' . filemtime($jsFile);
        }
        $this->modx->controller->addLastJavascript($jsTab);
    }

    /**
     * Validate goldprice fields before resource save; abort with message on failure.
     *
     * @param array $scriptProperties
     */
    public function onBeforeDocFormSave(array $scriptProperties)
    {
        /** @var modResource|null $resource */
        $resource = $this->modx->getOption('resource', $scriptProperties, null, true);
        if (!$resource || !$this->isMsProduct($resource)) {
            return;
        }

        // Tab posts only when opened / fields rendered; skip if payload absent.
        if (!isset($_POST['goldprice']) || !is_array($_POST['goldprice'])) {
            return;
        }

        if (!$this->initialize()) {
            $this->modx->event->output('[goldprice] Не удалось инициализировать компонент.');
            return;
        }

        $allowedGroups = [];
        foreach ($this->getGroupsList() as $row) {
            $allowedGroups[] = (int) $row['id'];
        }

        // Normalize group_id if combo sent display text (legacy misconfig).
        if (isset($_POST['goldprice']['group_id']) && !is_numeric($_POST['goldprice']['group_id'])) {
            $label = (string) $_POST['goldprice']['group_id'];
            $_POST['goldprice']['group_id'] = '0';
            foreach ($this->getGroupsList() as $row) {
                $want = $row['title'] . ' (' . $row['weight'] . ' г)';
                if ($label === $want) {
                    $_POST['goldprice']['group_id'] = (string) $row['id'];
                    break;
                }
            }
        }

        $result = \GoldPrice\Domain\Product\ProductFormPending::fromPost($_POST['goldprice'], $allowedGroups);
        if (!$result['ok']) {
            $this->modx->event->output(implode(' ', $result['errors']));
            return;
        }

        self::$pendingProductData = $result['pending'];
        self::$pendingProductId = (int) $resource->get('id');
    }

    /**
     * Persist validated goldprice_product row after successful resource save.
     *
     * @param array $scriptProperties
     */
    public function onDocFormSave(array $scriptProperties)
    {
        if (self::$pendingProductData === null || !self::$pendingProductId) {
            return;
        }

        /** @var modResource|null $resource */
        $resource = $this->modx->getOption('resource', $scriptProperties, null, true);
        if (!$resource || !$this->isMsProduct($resource)) {
            self::$pendingProductData = null;
            self::$pendingProductId = null;
            return;
        }

        $productId = (int) $resource->get('id');
        if ($productId !== self::$pendingProductId) {
            self::$pendingProductData = null;
            self::$pendingProductId = null;
            return;
        }

        if (!$this->initialize()) {
            return;
        }

        $this->saveProductData($productId, self::$pendingProductData);
        self::$pendingProductData = null;
        self::$pendingProductId = null;
        // Storefront reads goldprice_price, not goldprice_product — same as group save.
        $this->recalculatePrices();
    }

    /**
     * @param modResource|array $resource
     * @return bool
     */
    public function isMsProduct($resource)
    {
        if (is_object($resource) && $resource instanceof modResource) {
            return $resource->get('class_key') === 'msProduct';
        }
        if (is_array($resource)) {
            return isset($resource['class_key']) && $resource['class_key'] === 'msProduct';
        }

        return false;
    }

    /**
     * @param int $productId
     * @return array
     */
    public function getProductData($productId)
    {
        $defaults = [
            'product_id' => (int) $productId,
            'weight' => 0,
            'metal' => '',
            'coin_type' => '',
            'group_id' => null,
            'use_custom' => 0,
            'custom_pct' => 0,
            'custom_buy_pct' => 0,
            'custom_fix' => 0,
            'custom_buy_fix' => 0,
            'ignore_market' => 0,
            'fixed_price' => 0,
            'buyout_price' => 0,
        ];

        /** @var GoldPriceProduct|null $obj */
        $obj = $this->modx->getObject('GoldPriceProduct', ['product_id' => (int) $productId]);
        if (!$obj) {
            return $defaults;
        }

        return array_merge($defaults, $obj->toArray());
    }

    /**
     * @return array<int,array{id:int,weight:float,title:string}>
     */
    public function getGroupsList()
    {
        $out = [];
        $q = $this->modx->newQuery('GoldPriceGroup');
        $q->sortby('weight', 'ASC');
        /** @var GoldPriceGroup[] $rows */
        $rows = $this->modx->getCollection('GoldPriceGroup', $q);
        foreach ($rows as $row) {
            $out[] = [
                'id' => (int) $row->get('id'),
                'weight' => (float) $row->get('weight'),
                'title' => (string) $row->get('title'),
            ];
        }

        return $out;
    }

    /**
     * @param int $productId
     * @param array $data validated fields from ProductDataValidator
     * @return bool
     */
    public function saveProductData($productId, array $data)
    {
        $productId = (int) $productId;
        if ($productId <= 0) {
            return false;
        }

        /** @var GoldPriceProduct|null $obj */
        $obj = $this->modx->getObject('GoldPriceProduct', ['product_id' => $productId]);
        if (!$obj) {
            $obj = $this->modx->newObject('GoldPriceProduct');
            $obj->set('product_id', $productId);
        }

        $obj->fromArray([
            'weight' => (float) $data['weight'],
            'metal' => (string) $data['metal'],
            'coin_type' => (string) $data['coin_type'],
            'group_id' => $data['group_id'],
            'use_custom' => !empty($data['use_custom']) ? 1 : 0,
            'custom_pct' => (float) $data['custom_pct'],
            'custom_buy_pct' => isset($data['custom_buy_pct']) ? (float) $data['custom_buy_pct'] : 0.0,
            'custom_fix' => (float) $data['custom_fix'],
            'custom_buy_fix' => isset($data['custom_buy_fix']) ? (float) $data['custom_buy_fix'] : 0.0,
            'ignore_market' => !empty($data['ignore_market']) ? 1 : 0,
            'fixed_price' => (float) $data['fixed_price'],
            'buyout_price' => (float) $data['buyout_price'],
        ]);

        if (!$obj->save()) {
            $this->modx->log(modX::LOG_LEVEL_ERROR, '[goldprice] Failed to save product #' . $productId);
            return false;
        }

        return true;
    }

    /**
     * CMP and processors: mgr session plus Administrator-level settings permission.
     *
     * @return bool
     */
    public function isMgrAdmin()
    {
        if (!$this->modx->user || !(int) $this->modx->user->get('id')) {
            return false;
        }
        if (!$this->modx->user->isAuthenticated('mgr')) {
            return false;
        }

        return (bool) $this->modx->user->get('sudo') || $this->modx->hasPermission('settings');
    }

    /**
     * @param string $event
     * @param string $message
     * @param array $data
     * @return bool
     */
    public function writeLog($event, $message, array $data = [])
    {
        if (!$this->initialize()) {
            return false;
        }

        $log = $this->modx->newObject('GoldPriceLog');
        if (!$log) {
            return false;
        }

        $userId = ($this->modx->user && (int) $this->modx->user->get('id'))
            ? (int) $this->modx->user->get('id')
            : null;

        $log->fromArray([
            'created_at' => date('Y-m-d H:i:s'),
            'event' => (string) $event,
            'user_id' => $userId,
            'message' => (string) $message,
            'data' => json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);

        return (bool) $log->save();
    }

    /**
     * writeLog + email to active recipients subscribed to the event flag.
     *
     * @param string $event
     * @param array $data
     * @return bool
     */
    public function notify($event, array $data = [])
    {
        $this->modx->lexicon->load('goldprice:default');
        $event = (string) $event;

        if ($event === 'request_new') {
            $message = $this->modx->lexicon('goldprice.log_request_new', [
                'id' => isset($data['id']) ? (int) $data['id'] : 0,
                'amount' => isset($data['amount']) ? (string) $data['amount'] : '',
            ]);
        } elseif ($event === 'daily_limit') {
            $message = $this->modx->lexicon('goldprice.log_daily_limit', [
                'date' => isset($data['date']) ? (string) $data['date'] : date('Y-m-d'),
                'time' => isset($data['time']) ? (string) $data['time'] : date('H:i:s'),
                'sum' => isset($data['sum']) ? (string) $data['sum'] : '',
            ]);
        } elseif ($event === 'storm_on' || $event === 'storm_off') {
            $n = isset($data['groups']) && is_array($data['groups']) ? count($data['groups']) : 0;
            $message = $event . ' (' . $n . ')';
        } elseif ($event === 'api_error') {
            $message = isset($data['message']) ? (string) $data['message'] : $event;
        } else {
            $message = $event;
        }

        $ok = $this->writeLog($event, $message, $data);
        (new \GoldPrice\Service\Notifier($this->modx, $this))->send($event, $data);

        return $ok;
    }

    /**
     * Sum of amount for status=done requests created today (server date).
     * ponytail: window is created_at of done rows; add approved_at if calendar-day-of-approval is required.
     *
     * @return float
     */
    public function sumDoneBuyoutToday()
    {
        if (!$this->initialize()) {
            return 0.0;
        }

        $table = $this->modx->getTableName('GoldPriceRequest');
        $sql = "SELECT COALESCE(SUM(`amount`), 0) AS total
            FROM {$table}
            WHERE `status` = :status
              AND `created_at` >= :day_start
              AND `created_at` < :day_end";
        $stmt = $this->modx->prepare($sql);
        if (!$stmt) {
            return 0.0;
        }

        $dayStart = date('Y-m-d 00:00:00');
        $dayEnd = date('Y-m-d 00:00:00', strtotime('+1 day'));
        $stmt->bindValue(':status', 'done');
        $stmt->bindValue(':day_start', $dayStart);
        $stmt->bindValue(':day_end', $dayEnd);
        if (!$stmt->execute()) {
            return 0.0;
        }
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ? (float) $row['total'] : 0.0;
    }

    /**
     * Runs PriceRecalculator and strips internal price rows from the payload.
     *
     * @return array<string,mixed>
     */
    public function recalculatePrices()
    {
        $summary = (new \GoldPrice\Service\PriceRecalculator($this->modx, $this))->recalculate();
        unset($summary['prices'], $summary['obsolete']);

        return $summary;
    }

    /**
     * Replace miniShop2 price with the stored goldprice sale. Posted newprice is ignored.
     * Plugin event priority must be after msOptionsPrice: miniShop2 keeps only the last returnedValues.
     *
     * @param array $scriptProperties
     */
    public function onGetProductPrice(array $scriptProperties)
    {
        $decision = $this->cartDecision($this->productIdFromEvent($scriptProperties));
        if ($decision['action'] !== 'set') {
            return;
        }

        if (!is_array($this->modx->event->returnedValues)) {
            $this->modx->event->returnedValues = array();
        }
        $this->modx->event->returnedValues['price'] = $decision['price'];
    }

    /**
     * Block add-to-cart when out of stock, sale is frozen, or the quote is stale.
     *
     * @param array $scriptProperties
     */
    public function onBeforeAddToCart(array $scriptProperties)
    {
        $productId = $this->productIdFromEvent($scriptProperties);
        $decision = $this->cartDecision($productId, $this->productInStock($productId));
        if ($decision['action'] !== 'reject') {
            return;
        }

        $this->modx->lexicon->load('goldprice:default');
        if ($decision['reason'] === 'sale_frozen') {
            $message = (string) $this->modx->lexicon('goldprice.storm_sale_paused');
        } elseif ($decision['reason'] === 'out_of_stock') {
            $message = (string) $this->modx->lexicon('goldprice.cart_out_of_stock');
        } else {
            $message = (string) $this->modx->lexicon('goldprice.cart_price_unavailable');
        }
        $this->modx->event->output($message);
    }

    /**
     * Remember when the line entered the cart. Do not put the stamp in options —
     * it would change the miniShop2 cart key.
     *
     * @param array $scriptProperties
     */
    public function onAddToCart(array $scriptProperties)
    {
        $cart = isset($scriptProperties['cart']) ? $scriptProperties['cart'] : null;
        $key = isset($scriptProperties['key']) ? (string) $scriptProperties['key'] : '';
        if (!is_object($cart) || $key === '' || !method_exists($cart, 'get') || !method_exists($cart, 'set')) {
            return;
        }

        $items = $cart->get();
        if (!isset($items[$key]) || !is_array($items[$key])) {
            return;
        }

        $stamped = \GoldPrice\Domain\Storefront\CartExpiry::stamp($items[$key], time());
        if ($stamped === $items[$key]) {
            return;
        }
        $items[$key] = $stamped;
        $this->writeMs2Cart($cart, $items);
    }

    /**
     * Drop cart lines older than goldprice.cart_ttl (default 1 hour).
     */
    public function purgeExpiredCartItems()
    {
        if (PHP_SAPI === 'cli') {
            return 0;
        }
        $ctx = $this->modx->context ? (string) $this->modx->context->get('key') : '';
        if ($ctx === 'mgr' || $ctx === '') {
            return 0;
        }

        /** @var \miniShop2|null $ms2 */
        $ms2 = $this->modx->getService('miniShop2');
        if (!$ms2 || !is_object($ms2)) {
            return 0;
        }
        $ms2->initialize($ctx);
        if (empty($ms2->cart) || !is_object($ms2->cart) || !method_exists($ms2->cart, 'get')) {
            return 0;
        }

        $ttl = max(1, (int) $this->modx->getOption('goldprice.cart_ttl', null, 3600));
        $out = \GoldPrice\Domain\Storefront\CartExpiry::apply($ms2->cart->get(), time(), $ttl);
        if (!$out['changed']) {
            return 0;
        }
        $this->writeMs2Cart($ms2->cart, $out['items']);

        return $out['removed'];
    }

    /**
     * @param object $cart
     * @param array<string,mixed> $items
     */
    private function writeMs2Cart($cart, array $items)
    {
        if (method_exists($cart, 'set')) {
            $cart->set($items);
        }
        // msCartHandler::set() drops the $_SESSION reference — write session explicitly.
        if (!isset($_SESSION['minishop2']) || !is_array($_SESSION['minishop2'])) {
            $_SESSION['minishop2'] = array();
        }
        $_SESSION['minishop2']['cart'] = $items;
    }

    /**
     * @param array $scriptProperties
     * @return int
     */
    private function productIdFromEvent(array $scriptProperties)
    {
        $data = !empty($scriptProperties['data']) && is_array($scriptProperties['data'])
            ? $scriptProperties['data']
            : array();
        $product = !empty($scriptProperties['product']) ? $scriptProperties['product'] : null;
        $postId = !empty($_POST['id']) ? (int) $_POST['id'] : 0;

        return \GoldPrice\Domain\Storefront\CartPriceDecision::productId($product, $data, $postId);
    }

    /**
     * @param int $productId
     * @return array{action:string,price?:string,reason?:string}
     */
    private function cartDecision($productId, $inStock = true)
    {
        if ((int) $productId <= 0 || !$this->initialize()) {
            return ['action' => 'skip', 'reason' => 'no_product'];
        }

        $path = $this->config['core_path'];
        $row = \GoldPrice\Service\StorefrontPriceLoader::row($this->modx, $path, (int) $productId);
        $quoteAt = \GoldPrice\Service\StorefrontPriceLoader::quoteAt($this->modx, $path);
        $maxAge = (int) $this->modx->getOption('goldprice.quote_max_age', null, 900);

        return \GoldPrice\Domain\Storefront\CartPriceDecision::decide(
            $row,
            $quoteAt,
            time(),
            $maxAge,
            (bool) $inStock
        );
    }

    /**
     * @param int $productId
     */
    private function productInStock($productId): bool
    {
        if ((int) $productId <= 0 || !$this->initialize()) {
            return true;
        }

        $resource = $this->modx->getObject('modResource', (int) $productId);
        if (!$resource) {
            return true;
        }

        return \GoldPrice\Domain\Storefront\PriceAvailability::isInStock($resource->getTVValue('stocks'));
    }
}
