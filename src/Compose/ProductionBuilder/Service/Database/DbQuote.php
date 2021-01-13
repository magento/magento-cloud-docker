<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service\Database;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\Service\Database\Db\HealthCheck;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns DbQuote service configuration
 */
class DbQuote implements ServiceBuilderInterface
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
     * @var HealthCheck
     */
    private $healthCheck;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Volume $volume
     * @param HealthCheck $healthCheck
     */
    public function __construct(ServiceFactory $serviceFactory, Volume $volume, HealthCheck $healthCheck)
    {
        $this->serviceFactory = $serviceFactory;
        $this->volume = $volume;
        $this->healthCheck = $healthCheck;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_DB_QUOTE;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return ServiceInterface::SERVICE_DB_QUOTE;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $port = $config->getDbQuotePortsExpose();
        $dbConfig = [
            'ports' => [$port ? "$port:3306" : '3306'],
            'volumes' => $this->getMounts($config),
            BuilderInterface::SERVICE_HEALTHCHECK => $this->healthCheck->getConfig(),
        ];

        return $this->serviceFactory->create(
            ServiceInterface::SERVICE_DB_QUOTE,
            $config->getServiceVersion(ServiceInterface::SERVICE_DB),
            $dbConfig,
            $config->getServiceImage(ServiceInterface::SERVICE_DB),
            $config->getCustomRegistry()
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
        return [];
    }

    /**
     * @param Config $config
     * @return array
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    private function getMounts(Config $config): array
    {
        $mounts = $this->volume->getMount($config);

        if ($config->hasMariaDbConf()) {
            $mounts[] = BuilderInterface::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d';
        }

        $mounts[] = BuilderInterface::VOLUME_MAGENTO_DB_QUOTE . ':/var/lib/mysql';
        $mounts[] = BuilderInterface::VOLUME_DOCKER_ENTRYPOINT_QUOTE . ':/docker-entrypoint-initdb.d';

        return $mounts;
    }
}
