<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Generates relationship data for current configuration
 * based on services in .magento/service.yaml and relationships in .magento.app.yaml
 */
class Relationship
{
    /**
     * Default relationships configuration
     *
     * @var array
     */
    private static $defaultConfiguration = [
        'database' => [
            [
                'host' => 'db',
                'path' => 'magento2',
                'password' => 'magento2',
                'username' => 'magento2',
                'port' => '3306'
            ],
        ],
        'redis' => [
            [
                'host' => 'redis',
                'port' => '6379'
            ]
        ],
        'elasticsearch' => [
            [
                'host' => 'elasticsearch',
                'port' => '9200',
            ],
        ],
        'rabbitmq' => [
            [
                'host' => 'rabbitmq',
                'port' => '5672',
                'username' => 'guest',
                'password' => 'guest',
            ]
        ],
    ];

    /**
     * Generates relationship data for current configuration
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function get(Config $config): array
    {
        $relationships = [];
        foreach (self::$defaultConfiguration as $serviceName => $serviceConfig) {
            if ($config->hasServiceEnabled($this->convertServiceName($serviceName))) {
                $relationships[$serviceName] = $serviceConfig;
            }
        }

        return $relationships;
    }

    /**
     * Convert services names for compatibility with `getServiceVersion` method.
     *
     * @param string $serviceName
     * @return string
     */
    private function convertServiceName(string $serviceName): string
    {
        $map = [
            'database' => ServiceInterface::SERVICE_DB
        ];

        return $map[$serviceName] ?? $serviceName;
    }
}
