<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;

require_once __DIR__ . '/../../vendor/autoload.php';

$config = Robo\Robo::createConfiguration(
    [file_exists('configuration.yml') ? 'configuration.yml' : 'configuration.dist.yml']
);

$container = Robo\Robo::createDefaultContainer(null, null, null, $config);
$container->delegate(new League\Container\ReflectionContainer());

$container->inflector(
    BuilderAwareInterface::class,
    function (BuilderAwareInterface $commandClass) use ($container) {
        $builder = CollectionBuilder::create($container, $commandClass);
        $commandClass->setBuilder($builder);
    }
);

Robo\Robo::setContainer($container);

return $container;
