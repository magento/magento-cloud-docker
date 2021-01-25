<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Magento\CloudDocker\App\ContainerInterface;
use Magento\CloudDocker\Filesystem\DirectoryList;

/**
 * Creates instances of config repository.
 */
class ConfigFactory
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;
    }

    /**
     * @param array $sources
     * @return Config
     */
    public function create(array $sources): Config
    {
        return new Config($this->container->get(DirectoryList::class), $sources);
    }
}
