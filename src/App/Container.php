<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\App;

use Magento\CloudDocker\Filesystem\DirectoryList;
use Symfony\Component\DependencyInjection\ContainerBuilder;
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
     * @param string|null $eceToolsRoot
     * @throws ContainerException
     */
    public function __construct(string $root, string $magentoRoot, string $eceToolsRoot = null)
    {
        $container = new ContainerBuilder();
        $container->set(DirectoryList::class, new DirectoryList(
            $root,
            $magentoRoot,
            $eceToolsRoot
        ));

        try {
            $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/../../config'));
            $loader->load('services.xml');
        } catch (Exception $exception) {
            throw new ContainerException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $container->compile();

        $this->container = $container;
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
     * @inheritdoc
     */
    public function create(string $abstract, array $params = [])
    {
        if (empty($params) && $this->has($abstract)) {
            return $this->get($abstract);
        }

        return new $abstract(...array_values($params));
    }
}
