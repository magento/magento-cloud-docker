<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns Web service configuration
 */
class Web implements ServiceBuilderInterface
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var Volume
     */
    private $volume;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Volume $volume
     */
    public function __construct(ServiceFactory $serviceFactory, Volume $volume)
    {
        $this->serviceFactory = $serviceFactory;
        $this->volume = $volume;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_WEB;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return ServiceInterface::SERVICE_NGINX;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $result = $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion(ServiceInterface::SERVICE_NGINX),
            [
                'volumes' => $this->volume->getRo($config),
                'environment' => [
                    'WITH_XDEBUG=' . (int)$config->hasServiceEnabled(ServiceInterface::SERVICE_FPM_XDEBUG),
                    'NGINX_WORKER_PROCESSES=' . $config->getNginxWorkerProcesses(),
                    'NGINX_WORKER_CONNECTIONS=' . $config->getNginxWorkerConnections(),
                ],
            ],
            $config->getServiceImage(ServiceInterface::SERVICE_NGINX),
            $config->getCustomRegistry(),
            $config->getServiceImagePattern(ServiceInterface::SERVICE_NGINX)
        );

        if (!$config->hasServiceEnabled(ServiceInterface::SERVICE_TLS)
            && !$config->hasServiceEnabled(ServiceInterface::SERVICE_VARNISH)
        ) {
            $result['ports'] = [$config->getPort() . ':8080'];
            $result['networks'] = [
                BuilderInterface::NETWORK_MAGENTO => [
                    'aliases' => [$config->getHost()]
                ]
            ];
        }

        return $result;
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
        return [BuilderInterface::SERVICE_FPM => []];
    }
}
