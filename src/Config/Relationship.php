<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Magento\CloudDocker\Service\Config;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Generates relationship data for current configuration
 * based on services in .magento/service.yaml and relationships in .magento.app.yaml
 */
class Relationship
{
    public const RELATIONSHIP_DATABASE = 'database';
    public const RELATIONSHIP_DATABASE_SALES = 'database-sales';
    public const RELATIONSHIP_DATABASE_QUOTE = 'database-quote';
    /**
     * @var Config
     */
    private $config;

    /**
     * Default relationships configuration
     *
     * @var array
     */
    private static $defaultConfiguration = [
        self::RELATIONSHIP_DATABASE => [
            [
                'host' => 'db',
                'path' => 'magento2',
                'password' => 'magento2',
                'username' => 'magento2',
                'port' => '3306'
            ],
        ],
        self::RELATIONSHIP_DATABASE_SALES => [
            [
                'host' => 'db-sales',
                'path' => 'magento2',
                'password' => 'magento2',
                'username' => 'magento2',
                'port' => '3306'
            ],
        ],
        self::RELATIONSHIP_DATABASE_QUOTE => [
            [
                'host' => 'db-quote',
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
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Generates relationship data for current configuration
     *
     * @throws ConfigurationMismatchException
     */
    public function get(): array
    {
        $relationships = [];
        foreach (self::$defaultConfiguration as $serviceName => $serviceConfig) {
            if ($this->config->getServiceVersion($this->convertServiceName($serviceName))) {
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
            'database' => ServiceInterface::NAME_DB,
            'database-sales' => ServiceInterface::NAME_DB,
            'database-quote' => ServiceInterface::NAME_DB,
        ];

        return $map[$serviceName] ?? $serviceName;
    }
}
