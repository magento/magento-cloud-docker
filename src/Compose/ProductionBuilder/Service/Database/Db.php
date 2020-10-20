<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service\Database;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns Db service configuration
 */
class Db implements ServiceBuilderInterface
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
        return BuilderInterface::SERVICE_DB;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return ServiceInterface::SERVICE_DB;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $port = $config->getDbPortsExpose();
        $dbConfig = [
            'ports' => [$port ? "$port:3306" : '3306'],
            'volumes' => $this->getMounts($config),
            BuilderInterface::SERVICE_HEALTHCHECK => [
                'test' => 'mysqladmin ping -h localhost',
                'interval' => '30s',
                'timeout' => '30s',
                'retries' => 3
            ],
        ];

        if ($commands = $this->getCommands($config)) {
            $dbConfig['command'] = implode(' ', $commands);
        }

        return $this->serviceFactory->create(
            ServiceInterface::SERVICE_DB,
            $config->getServiceVersion(ServiceInterface::SERVICE_DB),
            $dbConfig,
            $config->getServiceImage(ServiceInterface::SERVICE_DB)
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

        $mounts[] = $config->getNameWithPrefix() . BuilderInterface::VOLUME_MAGENTO_DB . ':/var/lib/mysql';

        if ($config->hasDbEntrypoint()) {
            $mounts[] = BuilderInterface::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d';
        }

        return $mounts;
    }

    /**
     * @param Config $config
     * @return array
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    private function getCommands(Config $config): array
    {
        $commands = [];
        if ($config->getDbIncrementIncrement() > 1) {
            $commands[] = '--auto_increment_increment=' . $config->getDbIncrementIncrement();
        }

        if ($config->getDbIncrementOffset() > 1) {
            $commands[] = '--auto_increment_offset=' . $config->getDbIncrementOffset();
        }

        return $commands;
    }
}
