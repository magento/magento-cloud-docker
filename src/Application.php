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
     * @inheritdoc
     */
    protected function getDefaultCommands()
    {
        return array_merge(parent::getDefaultCommands(), [
            $this->container->get(Command\BuildCompose::class),
            $this->container->get(Command\BuildCustomCompose::class),
            $this->container->get(Command\BuildDist::class),
            $this->container->get(Command\Image\GeneratePhp::class)
        ]);
    }
}
