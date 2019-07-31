<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
if (!defined('BP')) {
    define('BP', dirname(__DIR__, 3));
}

if (!defined('ECE_BP')) {
    define('ECE_BP', BP);
}

foreach ([__DIR__ . '/../../autoload.php', __DIR__ . '/vendor/autoload.php'] as $file) {
    if (file_exists($file)) {
        return require $file;
    }
}

throw new \RuntimeException('Required file \'autoload.php\' was not found.');

