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
    public const SERVICE_GENERIC = 'generic';
    public const SERVICE_CLI = 'php-cli';
    public const SERVICE_FPM = 'php-fpm';
    public const SERVICE_FPM_XDEBUG = 'xdebug';
    public const SERVICE_REDIS = 'redis';
    public const SERVICE_DB = 'db';
    public const SERVICE_NGINX = 'nginx';
    public const SERVICE_VARNISH = 'varnish';
    public const SERVICE_ELASTICSEARCH = 'elasticsearch';
    public const SERVICE_RABBIT_MQ = 'rabbitmq';
    public const SERVICE_TLS = 'tls';
    public const SERVICE_NODE = 'node';
    public const SERVICE_SELENIUM = 'selenium';
    public const SERVICE_SELENIUM_IMAGE = 'selenium-image';
    public const SERVICE_SELENIUM_VERSION = 'selenium-version';

    private const PATTERN_STD = '%s:%s';
    private const PATTERN_VERSIONED = '%s:%s-%s';

    /**
     * @var array
     */
    private static $config = [
        self::SERVICE_CLI => [
            'image' => 'magento/magento-cloud-docker-php',
            'pattern' => '%s:%s-cli-%s',
            'config' => [
                'extends' => self::SERVICE_GENERIC
            ]
        ],
        self::SERVICE_FPM => [
            'image' => 'magento/magento-cloud-docker-php',
            'ports' => [9000],
            'pattern' => '%s:%s-fpm-%s',
            'config' => [
                'extends' => self::SERVICE_GENERIC
            ]
        ],
        self::SERVICE_FPM_XDEBUG => [
            'image' => 'magento/magento-cloud-docker-php',
            'pattern' => '%s:%s-fpm-%s',
            'config' => [
                'extends' => self::SERVICE_GENERIC,
                'ports' => [
                    '9001:9001',
                ]
            ]
        ],
        self::SERVICE_DB => [
            'image' => 'mariadb',
            'pattern' => self::PATTERN_STD,
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
            'image' => 'magento/magento-cloud-docker-nginx',
            'pattern' => self::PATTERN_VERSIONED,
            'config' => [
                'extends' => self::SERVICE_GENERIC,
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
        self::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish',
            'pattern' => self::PATTERN_VERSIONED,
        ],
        self::SERVICE_TLS => [
            'image' => 'magento/magento-cloud-docker-tls',
            'pattern' => self::PATTERN_VERSIONED,
            'versions' => ['latest'],
            'config' => [
                'ports' => [
                    '443:443'
                ],
            ]
        ],
        self::SERVICE_REDIS => [
            'image' => 'redis',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
            ]
        ],
        self::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch',
            'pattern' => self::PATTERN_VERSIONED
        ],
        self::SERVICE_RABBIT_MQ => [
            'image' => 'rabbitmq',
            'pattern' => self::PATTERN_STD
        ],
        self::SERVICE_NODE => [
            'image' => 'node',
            'pattern' => self::PATTERN_STD
        ],
        self::SERVICE_GENERIC => [
            'image' => 'alpine',
            'pattern' => '%s'
        ],
        self::SERVICE_SELENIUM => [
            'image' => 'selenium/standalone-chrome',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'ports' => [4444],
                'extends' => self::SERVICE_GENERIC
            ]
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
     * @param array $config
     * @param string $image
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function create(string $name, string $version, array $config = [], string $image = null): array
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

        /** Extract minor version. Patch version should not affect images. */
        preg_match('/^\d+\.\d+/', $mcdVersion, $matches);

        $image = $image ?: $metaConfig['image'];
        $pattern = $metaConfig['pattern'];

        return array_replace(
            ['image' => sprintf($pattern, $image, $version, $matches[0])],
            $defaultConfig,
            $config
        );
    }
}
