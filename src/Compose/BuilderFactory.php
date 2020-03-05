<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Psr\Container\ContainerInterface;

/**
 * Factory class for Docker builder.
 *
 * @codeCoverageIgnore
 */
class BuilderFactory
{
    public const BUILDER_DEVELOPER = 'developer';
    public const BUILDER_PRODUCTION = 'production';

    /**
     * @var array
     */
    private $strategies;

    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @param ContainerInterface $container
     * @param array $strategies
     */
    public function __construct(ContainerInterface $container, array $strategies)
    {
        $this->container = $container;
        $this->strategies = $strategies;
    }

    /**
     * @param string $strategy
     * @return BuilderInterface
     * @throws ConfigurationMismatchException
     */
    public function create(string $strategy): BuilderInterface
    {
        if (!array_key_exists($strategy, $this->strategies)) {
            throw new ConfigurationMismatchException(
                sprintf('Wrong mode "%s" passed', $strategy)
            );
        }

        return $this->container->get($this->strategies[$strategy]);
    }
}
