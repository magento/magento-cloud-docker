<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Service;

use Composer\Factory;
use Composer\IO\NullIO;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Filesystem\FileList;

/**
 * Create instance of Docker service configuration.
 */
class ServiceFactory
{
    public const SERVICE_CLI = 'php-cli';
    public const SERVICE_CLI_DEV = 'php-cli-dev';
    public const SERVICE_FPM = 'php-fpm';
    public const SERVICE_FPM_DEV = 'php-fpm-dev';
    public const SERVICE_REDIS = 'redis';
    public const SERVICE_DB = 'db';
    public const SERVICE_NGINX = 'nginx';
    public const SERVICE_VARNISH = 'varnish';
    public const SERVICE_ELASTICSEARCH = 'elasticsearch';
    public const SERVICE_RABBIT_MQ = 'rabbitmq';
    public const SERVICE_TLS = 'tls';
    public const SERVICE_NODE = 'node';
    public const SERVICE_GENERIC = 'generic';

    /**
     * @var array
     */
    private static $config = [
        self::SERVICE_CLI => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli-%s',
            'config' => [
                'extends' => 'generic'
            ]
        ],
        self::SERVICE_CLI_DEV => [
            'image' => 'magento/magento-cloud-docker-php:%s-cli-dev-%s',
            'config' => [
                'extends' => 'generic'
            ]
        ],
        self::SERVICE_FPM => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm-%s',
            'config' => [
                'extends' => 'generic'
            ]
        ],
        self::SERVICE_FPM_DEV => [
            'image' => 'magento/magento-cloud-docker-php:%s-fpm-dev-%s',
            'config' => [
                'extends' => 'generic'
            ]
        ],
        self::SERVICE_DB => [
            'image' => 'mariadb:%s',
            'config' => [
                'environment' => [
                    'MYSQL_ROOT_PASSWORD=magento2',
                    'MYSQL_DATABASE=magento2',
                    'MYSQL_USER=magento2',
                    'MYSQL_PASSWORD=magento2',
                ]
            ]
        ],
        self::SERVICE_NGINX => [
            'image' => 'magento/magento-cloud-docker-nginx:%s-%s',
            'config' => [
                'extends' => 'generic'
            ]
        ],
        self::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish:%s-%s',
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
            'image' => 'magento/magento-cloud-docker-tls:%s-%s',
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
            'image' => 'magento/magento-cloud-docker-elasticsearch:%s-%s'
        ],
        self::SERVICE_RABBIT_MQ => [
            'image' => 'rabbitmq:%s',
        ],
        self::SERVICE_NODE => [
            'image' => 'node:%s',
        ],
        self::SERVICE_GENERIC => [
            'image' => 'alpine',
        ]
    ];

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param FileList $fileList
     */
    public function __construct(FileList $fileList)
    {
        $this->fileList = $fileList;
    }

    /**
     * @param string $name
     * @param string $version
     * @param array $extendedConfig
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function create(string $name, string $version, array $extendedConfig = []): array
    {
        if (!array_key_exists($name, self::$config)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $name
            ));
        }

        $metaConfig = self::$config[$name];
        $defaultConfig = $metaConfig['config'] ?? [];

        $mcdVersion = Factory::create(new NullIO(), $this->fileList->getComposer())
            ->getPackage()
            ->getVersion();

        /** Extract minor version. Patch version must not affect images. */
        preg_match('/^\d+\.\d+/', $mcdVersion, $matches);

        return array_replace(
            ['image' => sprintf($metaConfig['image'], $version, $matches[0])],
            $defaultConfig,
            $extendedConfig
        );
    }
}
