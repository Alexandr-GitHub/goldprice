<?php
/**
 * Session CSRF token for the storefront buyout form.
 *
 * @var modX $modx
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

if (empty($_SESSION['goldprice_buyout_token']) || !is_string($_SESSION['goldprice_buyout_token'])) {
    $_SESSION['goldprice_buyout_token'] = bin2hex(random_bytes(16));
}

return (string) $_SESSION['goldprice_buyout_token'];
