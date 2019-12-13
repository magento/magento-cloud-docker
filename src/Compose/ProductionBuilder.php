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
use Magento\CloudDocker\Config\Application\Reader as AppReader;
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
    /**
     * @var array
     */
    private static $cliDepends = [
        self::SERVICE_DB => [
            'condition' => 'service_started'
        ],
        self::SERVICE_REDIS => [
            'condition' => 'service_started'
        ],
        self::SERVICE_ELASTICSEARCH => [
            'condition' => 'service_healthy'
        ],
        self::SERVICE_NODE => [
            'condition' => 'service_started'
        ],
        self::SERVICE_RABBITMQ => [
            'condition' => 'service_started'
        ]
    ];

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
     * @var AppReader
     */
    private $appReader;

    /**
     * @var ManagerFactory
     */
    private $managerFactory;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Config $serviceConfig
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param ManagerFactory $managerFactory
     * @param Resolver $resolver
     * @param EnvReader $envReader
     * @param AppReader $appReader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        ManagerFactory $managerFactory,
        Resolver $resolver,
        EnvReader $envReader,
        AppReader $appReader
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->serviceConfig = $serviceConfig;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->managerFactory = $managerFactory;
        $this->resolver = $resolver;
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
    public function build(Repository $config): Manager
    {
        $manager = $this->managerFactory->create();

        $phpVersion = $config->get(ServiceInterface::NAME_PHP) ?: $this->serviceConfig->getPhpVersion();
        $dbVersion = $config->get(ServiceInterface::NAME_DB)
            ?: $this->getServiceVersion(ServiceInterface::NAME_DB);
        $hostPort = $config->get(self::KEY_EXPOSE_DB_PORT);
        $dbPorts = $hostPort ? "$hostPort:3306" : '3306';

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $rootPath = $this->resolver->getRootPath();

        foreach ($this->getMagentoVolumes($config, false) as $volume) {
            $volumeConfig = explode(':', $volume);
            $volumeName = reset($volumeConfig);

            $manager->addVolume(
                $volumeName,
                $volumes[$volumeName] ?? []
            );
        }

        $manager->addVolume(self::VOLUME_MAGENTO, [
            'driver_opts' => [
                'type' => 'none',
                'device' => $rootPath,
                'o' => 'bind'
            ]
        ]);
        $manager->addVolumes([
            self::VOLUME_MAGENTO_DB => []
        ]);

        if ($this->hasSelenium($config)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DEV, []);
        }

        if (!$config->get(self::KEY_NO_TMP_MOUNTS)) {
            $manager->addVolume(self::VOLUME_DOCKER_TMP, [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath . '/.docker/tmp',
                    'o' => 'bind'
                ]
            ]);
            $manager->addVolume(self::VOLUME_DOCKER_MNT, [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath . '/.docker/mnt',
                    'o' => 'bind'
                ]
            ]);
        }

        $manager->addService(
            self::SERVICE_DB,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_DB,
                $dbVersion,
                [
                    'ports' => [$dbPorts],
                    'volumes' => array_merge(
                        [
                            self::VOLUME_MAGENTO_DB . ':/var/lib/mysql',
                            '.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d'
                        ],
                        $this->getDockerMount($config)
                    )
                ]
            ),
            [self::NETWORK_MAGENTO],
            []
        );

        $redisVersion = $config->get(ServiceInterface::NAME_REDIS) ?:
            $this->getServiceVersion(ServiceInterface::NAME_REDIS);

        if ($redisVersion) {
            $manager->addService(
                self::SERVICE_REDIS,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_REDIS,
                    $redisVersion
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $esVersion = $config->get(ServiceInterface::NAME_ELASTICSEARCH)
            ?: $this->getServiceVersion(ServiceInterface::NAME_ELASTICSEARCH);

        if ($esVersion) {
            $manager->addService(
                self::SERVICE_ELASTICSEARCH,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_ELASTICSEARCH,
                    $esVersion
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $nodeVersion = $config->get(ServiceInterface::NAME_NODE);

        if ($nodeVersion) {
            $manager->addService(
                self::SERVICE_NODE,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_NODE,
                    $nodeVersion,
                    ['volumes_from' => [self::SERVICE_VOLUMES_RO]]
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $rabbitMQVersion = $config->get(ServiceInterface::NAME_RABBITMQ)
            ?: $this->getServiceVersion(ServiceInterface::NAME_RABBITMQ);

        if ($rabbitMQVersion) {
            $manager->addService(
                self::SERVICE_RABBITMQ,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_RABBIT_MQ,
                    $rabbitMQVersion
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }
        $manager->addService(
            self::SERVICE_FPM,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_FPM,
                $phpVersion,
                ['volumes_from' => [self::SERVICE_VOLUMES_RO]]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_DB => []]
        );
        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_NGINX,
                $config->get(ServiceInterface::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
                ['volumes_from' => [self::SERVICE_VOLUMES_RO]]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_FPM => []]
        );
        $manager->addService(
            self::SERVICE_VARNISH,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_VARNISH,
                self::DEFAULT_VARNISH_VERSION
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_WEB => []]
        );
        $manager->addService(
            self::SERVICE_TLS,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_TLS,
                self::DEFAULT_TLS_VERSION,
                [
                    'networks' => [
                        self::NETWORK_MAGENTO => [
                            'aliases' => [Manager::DOMAIN]
                        ]
                    ]
                ]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_VARNISH => []]
        );

        if ($this->hasSelenium($config)) {
            $manager->addService(
                self::SERVICE_SELENIUM,
                $this->serviceFactory->create(
                    ServiceInterface::NAME_SELENIUM,
                    $config->get(ServiceFactory::SERVICE_SELENIUM_VERSION, 'latest'),
                    [],
                    $config->get(ServiceFactory::SERVICE_SELENIUM_IMAGE)
                ),
                [self::NETWORK_MAGENTO],
                [self::SERVICE_WEB => []]
            );
            $manager->addService(
                self::SERVICE_TEST,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_CLI,
                    $phpVersion,
                    ['volumes_from' => [self::SERVICE_VOLUMES_RW]]
                ),
                [self::NETWORK_MAGENTO],
                self::$cliDepends
            );
        }

        $phpExtensions = $this->phpExtension->get($phpVersion);

        /**
         * Generic service.
         */
        $manager->addService(
            self::SERVICE_GENERIC,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_GENERIC,
                '',
                [
                    'environment' => $this->converter->convert(array_merge(
                        $this->getVariables($config),
                        ['PHP_EXTENSIONS' => implode(' ', $phpExtensions)]
                    ))
                ]
            ),
            [],
            []
        );
        $manager->addService(
            self::SERVICE_BUILD,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_CLI,
                $phpVersion,
                ['volumes' => $this->getDefaultMagentoVolumes(false)]
            ),
            [self::NETWORK_MAGENTO_BUILD],
            self::$cliDepends
        );
        $manager->addService(
            self::SERVICE_DEPLOY,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_CLI,
                $phpVersion,
                ['volumes_from' => [self::SERVICE_VOLUMES_RO]]
            ),
            [self::NETWORK_MAGENTO],
            self::$cliDepends
        );

        if (!$config->get(self::KEY_NO_CRON, false)) {
            $manager->addService(
                self::SERVICE_CRON,
                $this->getCronCliService($phpVersion),
                [],
                self::$cliDepends
            );
        }
        $manager->addService(
            self::SERVICE_VOLUMES_RO,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_GENERIC,
                '',
                [
                    'volumes' => array_merge(
                        $this->getMagentoVolumes($config, true),
                        $this->getComposerVolumes()
                    )
                ]
            ),
            [],
            []
        );
        $manager->addService(
            self::SERVICE_VOLUMES_RW,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_GENERIC,
                '',
                [
                    'volumes' => array_merge(
                        $this->getMagentoVolumes($config, false),
                        $this->getComposerVolumes(),
                        $this->getDockerMount($config)
                    ),
                ]
            ),
            [],
            []
        );

        return $manager;
    }

    /**
     * @param Repository $config
     * @return bool
     */
    private function hasSelenium(Repository $config): bool
    {
        return $config->get(self::KEY_WITH_SELENIUM)
            || $config->get(ServiceInterface::NAME_SELENIUM)
            || $config->get(ServiceFactory::SERVICE_SELENIUM_IMAGE);
    }

    /**
     * @param string $version
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(string $version): array
    {
        $cliConfig = $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $version
        );

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
        $cliConfig['volumes_from'] = [self::SERVICE_VOLUMES_RO];

        return $cliConfig;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->fileList->getMagentoDockerCompose();
    }

    /**
     * @param Repository $config
     * @param bool $isReadOnly
     * @return array
     */
    private function getDefaultMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';
        $volumes = [
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
        ];

        return $volumes;
    }

    /**
     * @param Repository $config
     * @param bool $isReadOnly
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getMagentoVolumes(Repository $config, bool $isReadOnly): array
    {
        $volumes = $this->getDefaultMagentoVolumes($isReadOnly);

        try {
            $volumeConfiguration = $this->appReader->read()['mounts'];
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        foreach (array_keys($volumeConfiguration) as $volume) {
            $volumes[] = sprintf(
                '%s:%s:delegated',
                'magento-' . str_replace('/', '-', $volume),
                self::DIR_MAGENTO . '/' . $volume
            );
        }

        if ($this->hasSelenium($config)) {
            $volumes[] = self::VOLUME_MAGENTO_DEV . ':' . self::DIR_MAGENTO . '/dev:delegated';
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
     * @param Repository $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getVariables(Repository $config): array
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

        if ($this->hasSelenium($config)) {
            $variables['MFTF_UTILS'] = 1;
        }

        return array_merge($variables, $envConfig);
    }

    /**
     * @param string $serviceName
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    private function getServiceVersion(string $serviceName): ?string
    {
        return $this->serviceConfig->getServiceVersion($serviceName);
    }

    /**
     * @param Repository $config
     * @return array
     */
    private function getDockerMount(Repository $config): array
    {
        if ($config->get(self::KEY_NO_TMP_MOUNTS)) {
            return [];
        }

        return [
            self::VOLUME_DOCKER_MNT . ':/mnt',
            self::VOLUME_DOCKER_TMP . ':/tmp'
        ];
    }
}
