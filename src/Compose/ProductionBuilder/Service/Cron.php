<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\CliDepend;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceInterface as BuilderServiceInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 *
 */
class Cron implements BuilderServiceInterface
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
     * @var CliDepend
     */
    private $cliDepend;

    /**
     *
     * @param ServiceFactory $serviceFactory
     * @param Volume $volume
     * @param CliDepend $cliDepend
     */
    public function __construct(ServiceFactory $serviceFactory, Volume $volume, CliDepend $cliDepend)
    {
        $this->serviceFactory = $serviceFactory;
        $this->volume = $volume;
        $this->cliDepend = $cliDepend;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_REDIS;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $cron = $this->serviceFactory->create(
            ServiceInterface::SERVICE_PHP_CLI,
            $config->getServiceVersion(ServiceInterface::SERVICE_PHP),
            ['command' => 'run-cron']
        );
        $preparedCronConfig = [];

        foreach ($config->getCronJobs() as $job) {
            $preparedCronConfig[] = sprintf(
                '%s root cd %s && %s >> %s/var/log/cron.log',
                $job['schedule'],
                BuilderInterface::DIR_MAGENTO,
                str_replace('php ', '/usr/local/bin/php ', $job['command']),
                BuilderInterface::DIR_MAGENTO
            );
        }

        $cron['environment'] = [
            'CRONTAB' => implode(PHP_EOL, $preparedCronConfig)
        ];

        $cron['volumes'] = $this->volume->getRo($config);

        return $cron;
    }

    public function getNetworks(): array
    {
        return [BuilderInterface::NETWORK_MAGENTO];
    }

    public function getDependsOn(Config $config): array
    {
        return $this->cliDepend->getList($config);
    }
}
