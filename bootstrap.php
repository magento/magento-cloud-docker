<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CloudDocker\Filesystem\DirectoryList;
use Symfony\Component\DependencyInjection\ContainerBuilder;

error_reporting(E_ALL);
date_default_timezone_set('UTC');

require __DIR__ . '/autoload.php';

$container = new ContainerBuilder();
$container->set(DirectoryList::class, new DirectoryList(
    __DIR__,
    BP,
    ECE_BP
));

return $container;
