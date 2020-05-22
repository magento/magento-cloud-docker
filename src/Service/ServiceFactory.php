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
    private const PATTERN_STD = '%s:%s';
    private const PATTERN_VERSIONED = '%s:%s-%s';

    /**
     * Default mysql-* services configuration
     */
    private const SERVICE_DB_CONFIG = [
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
    ];

    /**
     * @var array
     */
    private static $config = [
        ServiceInterface::SERVICE_PHP_CLI => [
            'image' => 'magento/magento-cloud-docker-php',
            'pattern' => '%s:%s-cli-%s',
            'config' => [
                'extends' => ServiceInterface::SERVICE_GENERIC
            ]
        ],
        ServiceInterface::SERVICE_PHP_FPM => [
            'image' => 'magento/magento-cloud-docker-php',
            'ports' => [9000],
            'pattern' => '%s:%s-fpm-%s',
            'config' => [
                'extends' => ServiceInterface::SERVICE_GENERIC
            ]
        ],
        ServiceInterface::SERVICE_FPM_XDEBUG => [
            'image' => 'magento/magento-cloud-docker-php',
            'pattern' => '%s:%s-fpm-%s',
            'config' => [
                'extends' => ServiceInterface::SERVICE_GENERIC,
                'ports' => [
                    '9001:9001',
                ]
            ]
        ],
        ServiceInterface::SERVICE_DB => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_DB_QUOTE => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_DB_SALES => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_NGINX => [
            'image' => 'magento/magento-cloud-docker-nginx',
            'version' => 'latest',
            'pattern' => self::PATTERN_VERSIONED,
            'config' => [
                'extends' => ServiceInterface::SERVICE_GENERIC,
                'ports' => [
                    '80:80'
                ],
            ]
        ],
        ServiceInterface::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish',
            'version' => 'latest',
            'pattern' => self::PATTERN_VERSIONED,
        ],
        ServiceInterface::SERVICE_TLS => [
            'image' => 'magento/magento-cloud-docker-tls',
            'version' => 'latest',
            'pattern' => self::PATTERN_VERSIONED,
            'config' => [
                'ports' => [
                    '443:443'
                ],
            ]
        ],
        ServiceInterface::SERVICE_REDIS => [
            'image' => 'redis',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
            ]
        ],
        ServiceInterface::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch',
            'pattern' => self::PATTERN_VERSIONED
        ],
        ServiceInterface::SERVICE_RABBITMQ => [
            'image' => 'rabbitmq',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_NODE => [
            'image' => 'node',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_GENERIC => [
            'image' => 'alpine',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_SELENIUM => [
            'image' => 'selenium/standalone-chrome',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'ports' => [4444],
                'extends' => ServiceInterface::SERVICE_GENERIC
            ]
        ],
        ServiceInterface::SERVICE_BLACKFIRE => [
            'image' => 'blackfire/blackfire',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD
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

    /**
     * @param string $name
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getDefaultImage(string $name): string
    {
        if (isset(self::$config[$name]['image'])) {
            return self::$config[$name]['image'];
        }

        throw new ConfigurationMismatchException(sprintf(
            'Default image for %s cannot be resolved',
            $name
        ));
    }

    /**
     * @param string $name
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getDefaultVersion(string $name): string
    {
        if (isset(self::$config[$name]['version'])) {
            return self::$config[$name]['version'];
        }

        throw new ConfigurationMismatchException(sprintf(
            'Default version for %s cannot be resolved',
            $name
        ));
    }
}
