<?php
declare(strict_types=1);

/**
 * PHPUnit bootstrap — autoload domain classes without full MODX bootstrap.
 */

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (!is_file($autoload)) {
    fwrite(STDERR, "Run composer install in core/components/goldprice first.\n");
    exit(1);
}

require $autoload;
