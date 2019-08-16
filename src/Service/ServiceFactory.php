<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Service;

use Magento\CloudDocker\App\ConfigurationMismatchException;

/**
 * Create instance of Docker service configuration.
 */
class ServiceFactory
{
    const SERVICE_CLI = 'php-cli';
    const SERVICE_CLI_DEV = 'php-cli-dev';
    const SERVICE_FPM = 'php-fpm';
    const SERVICE_FPM_DEV = 'php-fpm-dev';
    const SERVICE_REDIS = 'redis';
    const SERVICE_DB = 'db';
    const SERVICE_NGINX = 'nginx';
    const SERVICE_VARNISH = 'varnish';
    const SERVICE_ELASTICSEARCH = 'elasticsearch';
    const SERVICE_RABBIT_MQ = 'rabbitmq';
    const SERVICE_TLS = 'tls';
    const SERVICE_NODE = 'node';

    const CONFIG = [
        self::SERVICE_CLI => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli'
        ],
        self::SERVICE_CLI_DEV => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli-dev'
        ],
        self::SERVICE_FPM => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm'
        ],
        self::SERVICE_FPM_DEV => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm-dev'
        ],
        self::SERVICE_DB => [
            'image' => 'mariadb:%s',
            'config' => [
                'volumes' => [
                    '/var/lib/mysql',
                    './.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d',
                ],
                'environment' => [
                    'MYSQL_ROOT_PASSWORD=magento2',
                    'MYSQL_DATABASE=magento2',
                    'MYSQL_USER=magento2',
                    'MYSQL_PASSWORD=magento2',
                ]
            ]
        ],
        self::SERVICE_NGINX => [
            'image' => 'magento/magento-cloud-docker-nginx:%s'
        ],
        self::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish:%s',
            'config' => [
                'environment' => [
                    'VIRTUAL_HOST=magento2.docker',
                    'VIRTUAL_PORT=80',
                    'HTTPS_METHOD=noredirect',
                ],
                'ports' => [
                    '80:80'
                ],
            ]
        ],
        self::SERVICE_TLS => [
            'image' => 'magento/magento-cloud-docker-tls:%s',
            'versions' => ['latest'],
            'config' => [
                'ports' => [
                    '443:443'
                ],
                'external_links' => [
                    'varnish:varnish'
                ]
            ]
        ],
        self::SERVICE_REDIS => [
            'image' => 'redis:%s',
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
            ]
        ],
        self::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch:%s'
        ],
        self::SERVICE_RABBIT_MQ => [
            'image' => 'rabbitmq:%s',
        ],
        self::SERVICE_NODE => [
            'image' => 'node:%s',
        ],
    ];

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function create(string $name, string $version, array $extendedConfig = []): array
    {
        if (!array_key_exists($name, self::CONFIG)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $name
            ));
        }

        $metaConfig = self::CONFIG[$name];
        $defaultConfig = $metaConfig['config'] ?? [];

        return array_replace(
            ['image' => sprintf($metaConfig['image'], $version)],
            $defaultConfig,
            $extendedConfig
        );
    }
}
