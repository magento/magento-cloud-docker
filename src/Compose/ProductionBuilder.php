<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Environment\Reader;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Service\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Production compose configuration.
 *
 * @codeCoverageIgnore
 */
class ProductionBuilder implements BuilderInterface
{
    public const DEFAULT_NGINX_VERSION = 'latest';
    public const DEFAULT_VARNISH_VERSION = 'latest';
    public const DEFAULT_TLS_VERSION = 'latest';

    public const SERVICE_PHP_CLI = ServiceFactory::SERVICE_CLI;
    public const SERVICE_PHP_FPM = ServiceFactory::SERVICE_FPM;

    public const KEY_NO_CRON = 'no-cron';
    public const KEY_EXPOSE_DB_PORT = 'expose-db-port';
    public const KEY_NO_TMP_MOUNTS = 'no-tmp-mounts';
    public const KEY_WITH_SELENIUM = 'with-selenium';

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var Config
     */
    private $serviceConfig;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ExtensionResolver
     */
    private $phpExtension;

    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Config $serviceConfig
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param Reader $reader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        Reader $reader
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->serviceConfig = $serviceConfig;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->reader = $reader;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function build(): array
    {
        $phpVersion = $this->config->get(ServiceInterface::NAME_PHP) ?: $this->getPhpVersion();
        $dbVersion = $this->config->get(ServiceInterface::NAME_DB)
            ?: $this->getServiceVersion(ServiceInterface::NAME_DB);
        $hostPort = $this->config->get(self::KEY_EXPOSE_DB_PORT);
        $dbPorts = $hostPort ? "$hostPort:3306" : '3306';

        $services = [
            'list' => [],
            'cliDepends' => []
        ];

        $services['list']['db'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_DB,
            $dbVersion,
            [
                'hostname' => 'db.magento2.docker',
                'ports' => [$dbPorts],
                'networks' => [
                    'magento' => [
                        'aliases' => [
                            'db.magento2.docker',
                        ],
                    ],
                ],
                'volumes' => array_merge(
                    [
                        'magento-db:/var/lib/mysql',
                        '.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d'
                    ],
                    $this->getDockerMount()
                )
            ]
        );
        $services['cliDepends']['db'] = [
            'condition' => 'service_started'
        ];

        $redisVersion = $this->config->get(ServiceInterface::NAME_REDIS) ?:
            $this->getServiceVersion(ServiceInterface::NAME_REDIS);

        if ($redisVersion) {
            $services['list']['redis'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_REDIS,
                $redisVersion,
                ['networks' => ['magento']]
            );
            $services['cliDepends']['redis'] = [
                'condition' => 'service_started'
            ];
        }

        $esVersion = $this->config->get(ServiceInterface::NAME_ELASTICSEARCH)
            ?: $this->getServiceVersion(ServiceInterface::NAME_ELASTICSEARCH);

        if ($esVersion) {
            $services['list']['elasticsearch'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_ELASTICSEARCH,
                $esVersion,
                ['networks' => ['magento']]
            );
            $services['cliDepends']['elasticsearch'] = [
                'condition' => 'service_healthy'
            ];
        }

        $nodeVersion = $this->config->get(ServiceInterface::NAME_NODE);

        if ($nodeVersion) {
            $services['list']['node'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_NODE,
                $nodeVersion,
                [
                    'volumes' => $this->getMagentoVolumes(false),
                    'networks' => ['magento'],
                ]
            );
            $services['cliDepends']['node'] = [
                'condition' => 'service_started'
            ];
        }

        $rabbitMQVersion = $this->config->get(ServiceInterface::NAME_RABBITMQ)
            ?: $this->getServiceVersion(ServiceInterface::NAME_RABBITMQ);

        if ($rabbitMQVersion) {
            $services['list']['rabbitmq'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_RABBIT_MQ,
                $rabbitMQVersion,
                ['networks' => ['magento']]
            );
            $services['cliDepends']['rabbitmq'] = [
                'condition' => 'service_started'
            ];
        }

        $cliDepends = $services['cliDepends'];

        $services['list']['fpm'] = $this->serviceFactory->create(
            static::SERVICE_PHP_FPM,
            $phpVersion,
            [
                'ports' => [9000],
                'depends_on' => ['db'],
                'volumes' => $this->getMagentoVolumes(true),
                'networks' => ['magento'],
            ]
        );
        $services['list']['build'] = $this->serviceFactory->create(
            static::SERVICE_PHP_CLI,
            $phpVersion,
            [
                'hostname' => 'build.magento2.docker',
                'volumes' => array_merge(
                    $this->getMagentoBuildVolumes(false),
                    $this->getComposerVolumes()
                ),
                'networks' => [
                    'magento'
                ],
            ]
        );
        $services['list']['deploy'] = $this->getCliService($phpVersion, true, $cliDepends, 'deploy.magento2.docker');
        $services['list']['web'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_NGINX,
            $this->config->get(ServiceInterface::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
            [
                'hostname' => 'web.magento2.docker',
                'depends_on' => ['fpm'],
                'volumes' => $this->getMagentoVolumes(true),
                'networks' => [
                    'magento'
                ],
            ]
        );
        $services['list']['varnish'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_VARNISH,
            self::DEFAULT_VARNISH_VERSION,
            [
                'depends_on' => ['web'],
                'networks' => [
                    'magento' => [
                        'aliases' => [
                            'magento2.docker',
                        ],
                    ],
                ],
            ]
        );
        $services['list']['tls'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_TLS,
            self::DEFAULT_TLS_VERSION,
            [
                'depends_on' => ['varnish'],
                'networks' => ['magento'],
            ]
        );

        if ($this->hasSelenium()) {
            $services['selenium'] = $this->serviceFactory->create(
                ServiceInterface::NAME_SELENIUM,
                $this->config->get(ServiceFactory::SERVICE_SELENIUM_VERSION, 'latest'),
                [
                    'hostname' => 'selenium.magento2.docker',
                    'depends_on' => ['web'],
                    'networks' => ['magento']
                ],
                $this->config->get(ServiceFactory::SERVICE_SELENIUM_IMAGE)
            );
        }

        /**
         * Generic service.
         */
        $phpExtensions = $this->getPhpExtensions($phpVersion);
        $services['list']['generic'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_GENERIC,
            '',
            [
                'environment' => $this->converter->convert(array_merge(
                    $this->getVariables(),
                    ['PHP_EXTENSIONS' => implode(' ', $phpExtensions)]
                ))
            ]
        );

        if (!$this->config->get(self::KEY_NO_CRON, false)) {
            $services['list']['cron'] = $this->getCronCliService(
                $phpVersion,
                true,
                $cliDepends,
                'cron.magento2.docker'
            );
        }

        return [
            'version' => '2.1',
            'services' => $services['list'],
            'volumes' => $this->getVolumesDefinition(),
            'networks' => [
                'magento' => [
                    'driver' => 'bridge',
                ],
                'magento-build' => [
                    'driver' => 'bridge',
                ],
            ],
        ];
    }

    /**
     * @return bool
     */
    private function hasSelenium(): bool
    {
        return $this->config->get(self::KEY_WITH_SELENIUM)
            || $this->config->get(ServiceInterface::NAME_SELENIUM)
            || $this->config->get(ServiceFactory::SERVICE_SELENIUM_IMAGE);
    }

    /**
     * @inheritDoc
     */
    public function setConfig(Repository $config): void
    {
        $this->config = $config;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(): Repository
    {
        return $this->config;
    }

    /**
     * @param string $version
     * @param bool $isReadOnly
     * @param array $depends
     * @param string $hostname
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(
        string $version,
        bool $isReadOnly,
        array $depends,
        string $hostname
    ): array {
        $cliConfig = $this->getCliService($version, $isReadOnly, $depends, $hostname);

        if ($cronConfig = $this->serviceConfig->getCron()) {
            $preparedCronConfig = [];

            foreach ($cronConfig as $job) {
                $preparedCronConfig[] = sprintf(
                    '%s root cd %s && %s >> %s/var/log/cron.log',
                    $job['spec'],
                    self::DIR_MAGENTO,
                    str_replace('php ', '/usr/local/bin/php ', $job['cmd']),
                    self::DIR_MAGENTO
                );
            }

            $cliConfig['environment'] = [
                'CRONTAB' => implode(PHP_EOL, $preparedCronConfig)
            ];
        }

        $cliConfig['command'] = 'run-cron';

        return $cliConfig;
    }

    /**
     * @param string $version
     * @param bool $isReadOnly
     * @param array $depends
     * @param string $hostname
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(
        string $version,
        bool $isReadOnly,
        array $depends,
        string $hostname
    ): array {
        return $this->serviceFactory->create(
            static::SERVICE_PHP_CLI,
            $version,
            [
                'hostname' => $hostname,
                'depends_on' => $depends,
                'volumes' => array_merge(
                    $this->getMagentoVolumes($isReadOnly),
                    $this->getComposerVolumes(),
                    $this->getDockerMount()
                ),
                'networks' => [
                    'magento' => [
                        'aliases' => [
                            $hostname,
                        ],
                    ],
                ],
            ]
        );
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->fileList->getMagentoDockerCompose();
    }

    /**
     * @return array
     */
    private function getVolumesDefinition(): array
    {
        $volumeConfig = [];
        $rootPath = $this->getRootPath();

        $volumes = [
            'magento' => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath,
                    'o' => 'bind'
                ]
            ],
            'magento-vendor' => $volumeConfig,
            'magento-generated' => $volumeConfig,
            'magento-var' => $volumeConfig,
            'magento-etc' => $volumeConfig,
            'magento-static' => $volumeConfig,
            'magento-media' => $volumeConfig,
            'magento-db' => $volumeConfig,
        ];

        if ($this->hasSelenium()) {
            $volumes['magento-dev'] = $volumeConfig;
        }

        if (!$this->config->get(self::KEY_NO_TMP_MOUNTS)) {
            $volumes['docker-tmp'] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath . '/.docker/tmp',
                    'o' => 'bind'
                ]
            ];
            $volumes['docker-mnt'] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath . '/.docker/mnt',
                    'o' => 'bind'
                ]
            ];
        }

        return $volumes;
    }

    /**
     * @return string
     */
    protected function getRootPath(): string
    {
        /**
         * For Windows we'll define variable in .env file
         *
         * WINDOWS_PWD=//C/www/my-project
         */
        if (stripos(PHP_OS, 'win') === 0) {
            return '${WINDOWS_PWD}';
        }

        return '${PWD}';
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        $volumes = [
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
            'magento-var:' . self::DIR_MAGENTO . '/var:delegated',
            'magento-etc:' . self::DIR_MAGENTO . '/app/etc:delegated',
            'magento-static:' . self::DIR_MAGENTO . '/pub/static:delegated',
            'magento-media:' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];

        if ($this->hasSelenium()) {
            $volumes[] = 'magento-dev:' . self::DIR_MAGENTO . '/dev:delegated';
        }

        return $volumes;
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        $volumes = [
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
        ];

        if ($this->hasSelenium()) {
            $volumes[] = 'magento-dev:' . self::DIR_MAGENTO . '/dev:delegated';
        }

        return $volumes;
    }

    /***
     * @return array
     */
    private function getComposerVolumes(): array
    {
        return [
            '~/.composer/cache:/root/.composer/cache:delegated',
        ];
    }

    /**
     * @return array
     *
     * @throws ConfigurationMismatchException
     */
    protected function getVariables(): array
    {
        try {
            $envConfig = $this->reader->read();
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        $variables = [
            'PHP_MEMORY_LIMIT' => '2048M',
            'UPLOAD_MAX_FILESIZE' => '64M',
            'MAGENTO_ROOT' => self::DIR_MAGENTO,
            # Name of your server in IDE
            'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
            # Docker host for developer environments, can be different for your OS
            'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
        ];

        if ($this->hasSelenium()) {
            $variables['MFTF_UTILS'] = 1;
        }

        return array_merge($variables, $envConfig);
    }

    /**
     * @param string $serviceName
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    protected function getServiceVersion(string $serviceName): ?string
    {
        return $this->serviceConfig->getServiceVersion($serviceName);
    }

    /**
     * @return string
     * @throws ConfigurationMismatchException
     */
    protected function getPhpVersion(): string
    {
        return $this->serviceConfig->getPhpVersion();
    }

    /**
     * @param string $phpVersion
     * @return array
     * @throws ConfigurationMismatchException
     */
    protected function getPhpExtensions(string $phpVersion): array
    {
        return $this->phpExtension->get($phpVersion);
    }

    /**
     * @return array
     */
    private function getDockerMount(): array
    {
        if ($this->config->get(self::KEY_NO_TMP_MOUNTS)) {
            return [];
        }

        return ['docker-mnt:/mnt', 'docker-tmp:/tmp'];
    }
}
