<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Magento\CloudDocker\App\ContainerInterface;

/**
 * The factory class for sources
 *
 * @see SourceInterface
 */
class SourceFactory
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
     * @param string $object
     * @return SourceInterface
     */
    public function create(string $object): SourceInterface
    {
        return $this->container->create($object);
    }
}
