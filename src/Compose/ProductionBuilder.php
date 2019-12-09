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
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Service\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;
use Magento\CloudDocker\Config\Application\Reader as AppReader;

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
     * @var EnvReader
     */
    private $envReader;

    /**
     * @var Repository
     */
    private $config;

    /**
     * @var AppReader
     */
    private $appReader;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Config $serviceConfig
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param EnvReader $envReader
     * @param AppReader $appReader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        EnvReader $envReader,
        AppReader $appReader
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->serviceConfig = $serviceConfig;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->envReader = $envReader;
        $this->appReader = $appReader;
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
            'db' => $this->serviceFactory->create(
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
            )
        ];

        $redisVersion = $this->config->get(ServiceInterface::NAME_REDIS) ?:
            $this->getServiceVersion(ServiceInterface::NAME_REDIS);

        if ($redisVersion) {
            $services['redis'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_REDIS,
                $redisVersion,
                ['networks' => ['magento']]
            );
        }

        $esVersion = $this->config->get(ServiceInterface::NAME_ELASTICSEARCH)
            ?: $this->getServiceVersion(ServiceInterface::NAME_ELASTICSEARCH);

        if ($esVersion) {
            $services['elasticsearch'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_ELASTICSEARCH,
                $esVersion,
                ['networks' => ['magento']]
            );
        }

        $nodeVersion = $this->config->get(ServiceInterface::NAME_NODE);

        if ($nodeVersion) {
            $services['node'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_NODE,
                $nodeVersion,
                [
                    'volumes' => $this->getMagentoVolumes(false),
                    'networks' => ['magento'],
                ]
            );
        }

        $rabbitMQVersion = $this->config->get(ServiceInterface::NAME_RABBITMQ)
            ?: $this->getServiceVersion(ServiceInterface::NAME_RABBITMQ);

        if ($rabbitMQVersion) {
            $services['rabbitmq'] = $this->serviceFactory->create(
                ServiceFactory::SERVICE_RABBIT_MQ,
                $rabbitMQVersion,
                ['networks' => ['magento']]
            );
        }

        $cliDepends = array_keys($services);

        $services['fpm'] = $this->serviceFactory->create(
            static::SERVICE_PHP_FPM,
            $phpVersion,
            [
                'ports' => [9000],
                'depends_on' => ['db'],
                'volumes' => $this->getMagentoVolumes(true),
                'networks' => ['magento'],
            ]
        );
        $services['build'] = $this->serviceFactory->create(
            static::SERVICE_PHP_CLI,
            $phpVersion,
            [
                'hostname' => 'build.magento2.docker',
                'depends_on' => $cliDepends,
                'volumes' => array_merge(
                    $this->getMagentoBuildVolumes(false),
                    $this->getComposerVolumes()
                ),
                'networks' => [
                    'magento-build' => [
                        'aliases' => [
                            'build.magento2.docker',
                        ],
                    ],
                ],
            ]
        );
        $services['deploy'] = $this->getCliService($phpVersion, true, $cliDepends, 'deploy.magento2.docker');
        $services['web'] = $this->serviceFactory->create(
            ServiceFactory::SERVICE_NGINX,
            $this->config->get(ServiceInterface::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
            [
                'hostname' => 'web.magento2.docker',
                'depends_on' => ['fpm'],
                'volumes' => $this->getMagentoVolumes(true),
                'networks' => [
                    'magento' => [
                        'aliases' => [
                            'web.magento2.docker',
                        ],
                    ],
                ],
            ]
        );
        $services['varnish'] = $this->serviceFactory->create(
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
        $services['tls'] = $this->serviceFactory->create(
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
            $services['test'] = $this->getCliService(
                $phpVersion,
                false,
                $cliDepends,
                'test.magento2.docker'
            );
        }

        /**
         * Generic service.
         */
        $phpExtensions = $this->getPhpExtensions($phpVersion);
        $services['generic'] = $this->serviceFactory->create(
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
            $services['cron'] = $this->getCronCliService(
                $phpVersion,
                true,
                $cliDepends,
                'cron.magento2.docker'
            );
        }

        return [
            'version' => '2',
            'services' => $services,
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
     * @throws FilesystemException
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
            'magento-db' => $volumeConfig,
        ];

        if ($this->hasSelenium()) {
            $volumes['magento-dev'] = $volumeConfig;
        }

        foreach ($this->getMagentoVolumes() as $volume) {
            $config = explode(':', $volume);
            $volumeName = reset($config);
            $volumes[$volumeName] = $volumes[$volumeName] ?? $volumeConfig;
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
    protected function getDefaultMagentoVolumes(bool $isReadOnly): array
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
            $envConfig = $this->envReader->read();
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

    /**
     * Retrieve configured volumes.
     *
     * @param bool $isReadOnly
     * @return array
     * @throws FilesystemException
     */
    protected function getMagentoVolumes(bool $isReadOnly = true): array
    {
        $volumes = $this->getDefaultMagentoVolumes($isReadOnly);
        $volumeConfiguration = $this->appReader->read()['mounts'];

        foreach (array_keys($volumeConfiguration) as $volume) {
            $volumes[] = sprintf(
                '%s:%s:delegated',
                'magento-' . str_replace('/', '-', $volume),
                self::DIR_MAGENTO . '/' . $volume
            );
        }

        return $volumes;
    }
}
