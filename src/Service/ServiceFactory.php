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
            'shm_size' => '2gb',
            'environment' => [
                'MYSQL_ROOT_PASSWORD=magento2',
                'MYSQL_DATABASE=magento2',
                'MYSQL_USER=magento2',
                'MYSQL_PASSWORD=magento2',
            ]
        ]
    ];

    /**
     * Default nginx configuration for nginx and tls services
     */
    private const SERVICE_NGINX_CONFIG = [
        'image' => 'magento/magento-cloud-docker-nginx',
        'version' => '1.24',
        'pattern' => self::PATTERN_VERSIONED,
        'config' => [
            'extends' => ServiceInterface::SERVICE_GENERIC,
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
            ]
        ],
        ServiceInterface::SERVICE_DB => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_DB_QUOTE => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_DB_SALES => self::SERVICE_DB_CONFIG,
        ServiceInterface::SERVICE_NGINX => self::SERVICE_NGINX_CONFIG,
        ServiceInterface::SERVICE_VARNISH => [
            'image' => 'magento/magento-cloud-docker-varnish',
            'version' => '6.6',
            'pattern' => self::PATTERN_VERSIONED,
        ],
        ServiceInterface::SERVICE_TLS => self::SERVICE_NGINX_CONFIG,
        ServiceInterface::SERVICE_REDIS => [
            'image' => 'redis',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'volumes' => [
                    '/data',
                ],
                'ports' => [6379],
                'sysctls' => [
                    'net.core.somaxconn' => 1024,
                ],
                'ulimits' => [
                    'nproc' => 65535,
                    'nofile' => [
                        'soft' => 20000,
                        'hard' => 40000
                    ],
                ]
            ],
        ],
        ServiceInterface::SERVICE_ELASTICSEARCH => [
            'image' => 'magento/magento-cloud-docker-elasticsearch',
            'pattern' => self::PATTERN_VERSIONED,
            'config' => [
                'ulimits' => [
                    'memlock' => [
                        'soft' => -1,
                        'hard' => -1
                    ]
                ]
            ]
        ],
        ServiceInterface::SERVICE_OPENSEARCH => [
            'image' => 'magento/magento-cloud-docker-opensearch',
            'pattern' => self::PATTERN_VERSIONED,
            'config' => [
                'ulimits' => [
                    'memlock' => [
                        'soft' => -1,
                        'hard' => -1
                    ]
                ]
            ]
        ],
        ServiceInterface::SERVICE_RABBITMQ => [
            'image' => 'rabbitmq',
            'pattern' => self::PATTERN_STD,
        ],
        ServiceInterface::SERVICE_NODE => [
            'image' => 'node',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_GENERIC => [
            'image' => 'magento/magento-cloud-docker-php',
            'version' => 'latest',
            'pattern' => '%s:%s-cli-%s'
        ],
        ServiceInterface::SERVICE_SELENIUM => [
            'image' => 'selenium/standalone-chrome',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD,
            'config' => [
                'ports' => [4444],
                'extends' => ServiceInterface::SERVICE_GENERIC,
                'shm_size' => '2gb'
            ]
        ],
        ServiceInterface::SERVICE_ZOOKEEPER => [
            'image' => 'zookeeper',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_BLACKFIRE => [
            'image' => 'blackfire/blackfire',
            'version' => 'latest',
            'pattern' => self::PATTERN_STD
        ],
        ServiceInterface::SERVICE_MAILHOG => [
            'image' => 'magento/magento-cloud-docker-mailhog',
            'version' => '1.0',
            'pattern' => self::PATTERN_VERSIONED,
        ]
    ];

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var string
     */
    private $mcdVersion;

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
     * @param string|null $image
     * @param string|null $customRegistry
     * @param string|null $imagePattern
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function create(
        string $name,
        string $version,
        array $config = [],
        ?string $image = null,
        ?string $customRegistry = null,
        ?string $imagePattern = null
    ): array {
        if (!array_key_exists($name, self::$config)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service "%s" is not supported',
                $name
            ));
        }

        $metaConfig = self::$config[$name];
        $defaultConfig = $metaConfig['config'] ?? [];

        $image = ($customRegistry ? $customRegistry . '/' : '') . ($image ?: $metaConfig['image']);
        $pattern = $imagePattern ?: $metaConfig['pattern'];

        return array_replace(
            ['image' => sprintf($pattern, $image, $version, $this->getMcdVersion())],
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

    /**
     * Returns patch version of magento-cloud-docker package
     *
     * @return string
     */
    private function getMcdVersion(): string
    {
        if ($this->mcdVersion === null) {
            $mcdVersion = Factory::create(new NullIO(), $this->fileList->getComposer())
                ->getPackage()
                ->getVersion();

            preg_match('/^\d+\.\d+\.\d+/', $mcdVersion, $matches);

            $this->mcdVersion = $matches[0];
        }

        return $this->mcdVersion;
    }
}
