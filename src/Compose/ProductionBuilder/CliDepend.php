<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns CLI dependencies depending on configuration
 */
class CliDepend
{
    /**
     * @var array
     */
    private static $cliDepends = [
        BuilderInterface::SERVICE_DB => [
            'condition' => 'service_healthy'
        ],
        BuilderInterface::SERVICE_REDIS => [
            'condition' => 'service_healthy'
        ],
        BuilderInterface::SERVICE_ELASTICSEARCH => [
            'condition' => 'service_healthy'
        ],
        BuilderInterface::SERVICE_NODE => [
            'condition' => 'service_started'
        ],
        BuilderInterface::SERVICE_RABBITMQ => [
            'condition' => 'service_started'
        ]
    ];

    /**
     * Returns list of CLI dependencies depending on given configuration
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getList(Config $config): array
    {
        $cliDepends = $this->getDefault();

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_QUOTE)) {
            $cliDepends = array_merge(
                $cliDepends,
                [BuilderInterface::SERVICE_DB_QUOTE => ['condition' => 'service_started']]
            );
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_SALES)) {
            $cliDepends = array_merge(
                $cliDepends,
                [BuilderInterface::SERVICE_DB_SALES => ['condition' => 'service_started']]
            );
        }

        return $cliDepends;
    }

    /**
     * Returns list of default dependencies
     *
     * @return array
     */
    public function getDefault(): array
    {
        return self::$cliDepends;
    }
}
