<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
define('BP', __DIR__);
define('DATA', __DIR__ . '/data');

error_reporting(E_ALL);
date_default_timezone_set('UTC');

return require __DIR__ . '/vendor/autoload.php';
