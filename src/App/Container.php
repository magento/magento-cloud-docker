<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\App;

use Magento\CloudDocker\Filesystem\DirectoryList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Config\FileLocator;
use Exception;

/**
 * Application container
 */
class Container implements ContainerInterface
{
    /**
     * @var \Symfony\Component\DependencyInjection\Container
     */
    private $container;

    /**
     * @param string $root
     * @param string $magentoRoot
     * @throws ContainerException
     */
    public function __construct(string $root, string $magentoRoot)
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->set('container', $this);
        $containerBuilder->setDefinition('container', new Definition(__CLASS__))
            ->setArguments([$root, $magentoRoot]);

        $containerBuilder->set(DirectoryList::class, new DirectoryList(
            $root,
            $magentoRoot
        ));

        try {
            $loader = new XmlFileLoader($containerBuilder, new FileLocator([__DIR__ . '/../../config']));
            $loader->load('services.xml');
        } catch (Exception $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $containerBuilder->compile();

        $this->container = $containerBuilder;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        return $this->container->get($id);
    }

    /**
     * @inheritDoc
     */
    public function has($id): bool
    {
        return $this->container->has($id);
    }

    /**
     * @inheritDoc
     */
    public function create(string $abstract, array $params = [])
    {
        if (empty($params) && $this->has($abstract)) {
            return $this->get($abstract);
        }

        return new $abstract(...array_values($params));
    }
}
