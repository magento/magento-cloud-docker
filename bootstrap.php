<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
error_reporting(E_ALL);
date_default_timezone_set('UTC');

require __DIR__ . '/autoload.php';

use Magento\CloudDocker\App\Container;

return new Container(__DIR__, BP, ECE_BP);
