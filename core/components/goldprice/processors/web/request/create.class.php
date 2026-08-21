<?php
/**
 * Public buyout request create (web context, no mgr auth).
 */
class GoldPriceWebRequestCreateProcessor extends modProcessor
{
    public function process()
    {
        $this->modx->lexicon->load('goldprice:default');

        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $token = (string) $this->getProperty('token', '');
        $sessionToken = isset($_SESSION['goldprice_buyout_token'])
            ? (string) $_SESSION['goldprice_buyout_token']
            : '';
        // Release lock early so parallel page requests do not hang.
        session_write_close();
        if ($token === '' || $sessionToken === '' || !hash_equals($sessionToken, $token)) {
            return $this->failure($this->modx->lexicon('goldprice.err_request_token'));
        }

        $validated = \GoldPrice\Domain\Buyout\BuyoutRequestValidator::validate([
            'website' => $this->getProperty('website', ''),
            'name' => $this->getProperty('name', ''),
            'phone' => $this->getProperty('phone', ''),
            'email' => $this->getProperty('email', ''),
            'count' => $this->getProperty('count', 0),
            'comment' => $this->getProperty('comment', ''),
        ]);
        if (!$validated['ok']) {
            return $this->failure($this->mapValidationError($validated['errors']));
        }

        $productId = (int) $this->getProperty('product_id', 0);
        if ($productId <= 0) {
            return $this->failure($this->modx->lexicon('goldprice.err_request_product'));
        }

        /** @var GoldPrice|null $gp */
        $gp = !empty($this->modx->goldprice) ? $this->modx->goldprice : null;
        if (!$gp || !$gp->initialize()) {
            return $this->failure($this->modx->lexicon('goldprice.err_init'));
        }

        $corePath = $gp->config['core_path'];
        $row = \GoldPrice\Service\StorefrontPriceLoader::row($this->modx, $corePath, $productId);
        $quoteAt = \GoldPrice\Service\StorefrontPriceLoader::quoteAt($this->modx, $corePath);
        $maxAge = (int) $this->modx->getOption('goldprice.quote_max_age', null, 900);
        $decision = \GoldPrice\Domain\Buyout\BuyoutPriceDecision::decide($row, $quoteAt, time(), $maxAge);
        if ($decision['action'] !== 'set') {
            return $this->failure($this->mapPriceReject($decision['reason'] ?? ''));
        }

        $price = (float) $decision['price'];
        $count = (int) $validated['data']['count'];
        $amountStr = \GoldPrice\Domain\Buyout\BuyoutLimits::calcAmount($price, $count);
        $amount = (float) $amountStr;

        $dealLimit = (float) $this->modx->getOption('goldprice.deal_buyout_limit', null, 0);
        if (\GoldPrice\Domain\Buyout\BuyoutLimits::isDealExceeded($amount, $dealLimit)) {
            return $this->failure($this->modx->lexicon('goldprice.err_deal_limit'));
        }

        $request = $this->modx->newObject('GoldPriceRequest');
        if (!$request) {
            return $this->failure($this->modx->lexicon('goldprice.err_save'));
        }

        $request->fromArray([
            'created_at' => date('Y-m-d H:i:s'),
            'product_id' => $productId,
            'price' => $price,
            'count' => $count,
            'amount' => $amount,
            'name' => $validated['data']['name'],
            'phone' => $validated['data']['phone'],
            'email' => $validated['data']['email'],
            'comment' => $validated['data']['comment'] !== '' ? $validated['data']['comment'] : null,
            'status' => 'new',
            'manager_id' => null,
        ]);

        if (!$request->save()) {
            return $this->failure($this->modx->lexicon('goldprice.err_save'));
        }

        $requestId = (int) $request->get('id');
        $gp->notify('request_new', [
            'id' => $requestId,
            'product_id' => $productId,
            'price' => $decision['price'],
            'count' => $count,
            'amount' => $amountStr,
            'name' => $validated['data']['name'],
            'phone' => $validated['data']['phone'],
            'email' => $validated['data']['email'],
        ]);

        $dailyLimit = (float) $this->modx->getOption('goldprice.daily_buyout_limit', null, 0);
        $doneToday = $gp->sumDoneBuyoutToday();
        if (\GoldPrice\Domain\Buyout\BuyoutLimits::isDailyExceeded($doneToday, $amount, $dailyLimit)) {
            $gp->notify('daily_limit', [
                'date' => date('Y-m-d'),
                'time' => date('H:i:s'),
                'sum' => \GoldPrice\Domain\Money::roundMoney($doneToday + $amount),
                'limit' => $dailyLimit,
                'request_id' => $requestId,
                'context' => 'create',
            ]);
        }

        // Rotate token after successful submit.
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }
        $_SESSION['goldprice_buyout_token'] = bin2hex(random_bytes(16));
        session_write_close();

        return $this->success($this->modx->lexicon('goldprice.request_ok'), [
            'id' => $requestId,
        ]);
    }

    /**
     * @param string[] $errors
     */
    private function mapValidationError(array $errors): string
    {
        if (in_array('honeypot', $errors, true)) {
            return $this->modx->lexicon('goldprice.err_request_token');
        }
        if (in_array('name', $errors, true)) {
            return $this->modx->lexicon('goldprice.err_request_name');
        }
        if (in_array('phone', $errors, true)) {
            return $this->modx->lexicon('goldprice.err_request_phone');
        }
        if (in_array('email', $errors, true)) {
            return $this->modx->lexicon('goldprice.err_request_email');
        }
        if (in_array('count', $errors, true)) {
            return $this->modx->lexicon('goldprice.err_request_count');
        }

        return $this->modx->lexicon('goldprice.err_save');
    }

    private function mapPriceReject($reason): string
    {
        if ($reason === 'buy_frozen') {
            return $this->modx->lexicon('goldprice.storm_buy_paused');
        }
        if ($reason === 'stale') {
            return $this->modx->lexicon('goldprice.cart_price_unavailable');
        }

        return $this->modx->lexicon('goldprice.err_request_buy_unavailable');
    }
}

return 'GoldPriceWebRequestCreateProcessor';
