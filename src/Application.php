<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker;

use Magento\CloudDocker\Command;
use Psr\Container\ContainerInterface;

/**
 * @inheritdoc
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Application extends \Symfony\Component\Console\Application
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

        parent::__construct();
    }

    /**
     * @return ContainerInterface
     */
    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    /**
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(
            parent::getDefaultCommands(),
            [
                $this->container->get(Command\Build::class),
                $this->container->get(Command\GenerateDist::class),
                $this->container->get(Command\Generate\Php::class)
            ]
        );
    }
}
