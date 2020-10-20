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
use Magento\CloudDocker\Service\ServiceInterface;
use Magento\CloudDocker\App\ConfigurationMismatchException;

/**
 * Returns Tls service configuration
 */
class Tls implements ServiceBuilderInterface
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

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
        return ServiceInterface::SERVICE_TLS;
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
        return $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion($this->getServiceName()),
            [
                'networks' => [
                    BuilderInterface::NETWORK_MAGENTO => [
                        'aliases' => [$config->getHost()]
                    ]
                ],
                'environment' => ['UPSTREAM_HOST' => $this->getBackendService($config)],
                'ports' => [
                    $config->getPort() . ':80',
                    $config->getTlsPort() . ':443'
                ]
            ]
        );
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
        return [$this->getBackendService($config) => []];
    }

    /**
     * @param Config $config
     * @return string
     * @throws ConfigurationMismatchException
     */
    private function getBackendService(Config $config): string
    {
        return $config->hasServiceEnabled(ServiceInterface::SERVICE_VARNISH)
            ? BuilderInterface::SERVICE_VARNISH
            : BuilderInterface::SERVICE_WEB;
    }
}
