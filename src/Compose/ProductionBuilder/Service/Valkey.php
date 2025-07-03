<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 * Returns Redis service configuration
 */
class Valkey implements ServiceBuilderInterface
{
    /**
     * @var ServiceFactory
     */
    private ServiceFactory $serviceFactory;

    /**
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_VALKEY;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return $this->getName();
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $configArray = $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion($this->getServiceName()),
            [
            BuilderInterface::SERVICE_HEALTHCHECK => [
              'test' => 'valkey-cli ping || exit 1',
              'interval' => '30s',
              'timeout' => '30s',
              'retries' => 3
            ]
            ],
            $config->getServiceImage($this->getServiceName()),
            $config->getCustomRegistry()
        );

        // Set both 'cache' and 'valkey.magento2.docker' as aliases unconditionally
        $configArray['networks'][BuilderInterface::NETWORK_MAGENTO]['aliases'] = [
          'cache',
          'valkey.magento2.docker'
        ];

        return $configArray;
    }

    /**
     * @inheritDoc
     */
    public function getNetworks(): array
    {
        return [BuilderInterface::NETWORK_MAGENTO];
    }

    /**
     * @inheritDoc
     */
    public function getDependsOn(Config $config): array
    {
        return [];
    }
}
