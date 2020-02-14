<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\ProductionBuilder\VolumeResolver;
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
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class ProductionBuilder implements BuilderInterface
{
    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_NATIVE,
        self::SYNC_ENGINE_MOUNT
    ];

    public const SYNC_ENGINE_MOUNT = 'mount';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_MOUNT;

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
     * @var array
     */
    private static $standaloneServices = [
        self::SERVICE_REDIS,
        self::SERVICE_ELASTICSEARCH,
        self::SERVICE_RABBITMQ,
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
     * @var VolumeResolver
     */
    private $volumeResolver;

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
     * @param VolumeResolver $volumeResolver
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
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
        AppReader $appReader,
        VolumeResolver $volumeResolver
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
        $this->volumeResolver = $volumeResolver;
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

        $volumes = [
            self::VOLUME_MAGENTO_DB => [],
            self::VOLUME_DOCKER_ETRYPOINT => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/docker-entrypoint-initdb.d'),
                    'o' => 'bind'
                ]
            ],
            self::VOLUME_MARIADB_CONF => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/mariadb.conf.d'),
                    'o' => 'bind',
                ],
            ],
        ];

        if ($this->hasSelenium($config)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DEV, []);
        }

        if ($this->volumeResolver->getMountVolumes($config->has(self::KEY_NO_TMP_MOUNTS))) {
            $volumes[self::VOLUME_DOCKER_MNT] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mnt'),
                    'o' => 'bind'
                ]
            ];
        }

        $mounts = $this->appReader->read()['mounts'] ?? [];
        $hasSelenium = $this->hasSelenium($config);
        $excludeTmpMounts = (bool)$config->get(self::KEY_NO_TMP_MOUNTS);

        foreach ($this->volumeResolver->getMagentoVolumes($mounts, false, $hasSelenium) as $volumeName => $volume) {
            $syncConfig = [];

            if (!empty($volume['volume']) && $config->get(self::KEY_SYNC_ENGINE) === self::SYNC_ENGINE_NATIVE) {
                $syncConfig = [
                    'driver_opts' => [
                        'type' => 'none',
                        'device' => $this->resolver->getRootPath($volume['volume']),
                        'o' => 'bind'
                    ]
                ];
            }

            $volumes[$volumeName] = $syncConfig;
        }

        if ($config->get(self::KEY_SYNC_ENGINE) === self::SYNC_ENGINE_MOUNT) {
            $volumes[self::VOLUME_MAGENTO] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath(),
                    'o' => 'bind'
                ]
            ];
        }

        $manager->addVolumes($volumes);

        $volumesBuild = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getDefaultMagentoVolumes(false),
            $this->volumeResolver->getComposerVolumes()
        ));
        $volumesRo = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getMagentoVolumes($mounts, true, $hasSelenium),
            $this->volumeResolver->getMountVolumes($excludeTmpMounts)
        ));
        $volumesRw = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getMagentoVolumes($mounts, false, $hasSelenium),
            $this->volumeResolver->getMountVolumes($excludeTmpMounts),
            $this->volumeResolver->getComposerVolumes()
        ));
        $volumesMount = $this->volumeResolver->normalize(
            $this->volumeResolver->getMountVolumes($excludeTmpMounts)
        );

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
                            self::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d',
                            self::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d',
                        ],
                        $volumesMount
                    )
                ]
            ),
            [self::NETWORK_MAGENTO],
            []
        );

        foreach (self::$standaloneServices as $service) {
            $serviceVersion = $config->get($service) ?: $this->getServiceVersion($service);

            if ($serviceVersion) {
                $manager->addService(
                    $service,
                    $this->serviceFactory->create((string)$service, (string)$serviceVersion),
                    [self::NETWORK_MAGENTO],
                    []
                );
            }
        }

        $nodeVersion = $config->get(ServiceInterface::NAME_NODE);

        if ($nodeVersion) {
            $manager->addService(
                self::SERVICE_NODE,
                $this->serviceFactory->create(ServiceFactory::SERVICE_NODE, $nodeVersion, ['volumes' => $volumesRo]),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $manager->addService(
            self::SERVICE_FPM,
            $this->serviceFactory->create(ServiceFactory::SERVICE_FPM, $phpVersion, ['volumes' => $volumesRo]),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_DB => []]
        );
        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_NGINX,
                $config->get(ServiceInterface::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
                ['volumes' => $volumesRo]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_FPM => []]
        );

        if (!$config->get(self::KEY_NO_VARNISH, false)) {
            $manager->addService(
                self::SERVICE_VARNISH,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_VARNISH,
                    self::DEFAULT_VARNISH_VERSION,
                    [
                        'networks' => [
                            self::NETWORK_MAGENTO => [
                                'aliases' => [Manager::DOMAIN]
                            ]
                        ]
                    ]
                ),
                [],
                [self::SERVICE_WEB => []]
            );
        }

        $tlsBackendService = $config->get(self::KEY_NO_VARNISH, false) ? self::SERVICE_WEB : self::SERVICE_VARNISH;
        $manager->addService(
            self::SERVICE_TLS,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_TLS,
                self::DEFAULT_TLS_VERSION,
                [
                    'environment' => ['HTTPS_UPSTREAM_SERVER_ADDRESS' => $tlsBackendService],
                ]
            ),
            [self::NETWORK_MAGENTO],
            [$tlsBackendService => []]
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
                $this->serviceFactory->create(ServiceFactory::SERVICE_CLI, $phpVersion, ['volumes' => $volumesRw]),
                [self::NETWORK_MAGENTO],
                self::$cliDepends
            );
        }

        $phpExtensions = $this->phpExtension->get($phpVersion);

        /**
         * Include Xdebug if --with-xdebug is set
         */
        if ($config->get(self::KEY_WITH_XDEBUG, false)) {
            $manager->addService(
                self::SERVICE_FPM_XDEBUG,
                $this->serviceFactory->create(
                    ServiceFactory::SERVICE_FPM_XDEBUG,
                    $phpVersion,
                    [
                        'volumes' => $volumes,
                        'environment' => $this->converter->convert(array_merge(
                            ['PHP_EXTENSIONS' => implode(' ', $phpExtensions)]
                        ))
                    ]
                ),
                [self::NETWORK_MAGENTO],
                [self::SERVICE_DB => []]
            );
        }

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
            $this->serviceFactory->create(ServiceFactory::SERVICE_CLI, $phpVersion, ['volumes' => $volumesBuild]),
            [self::NETWORK_MAGENTO_BUILD],
            self::$cliDepends
        );
        $manager->addService(
            self::SERVICE_DEPLOY,
            $this->serviceFactory->create(ServiceFactory::SERVICE_CLI, $phpVersion, ['volumes' => $volumesRo]),
            [self::NETWORK_MAGENTO],
            self::$cliDepends
        );

        if ($config->get(self::KEY_WITH_CRON, false)) {
            $manager->addService(
                self::SERVICE_CRON,
                array_merge(
                    $this->getCronCliService($phpVersion),
                    ['volumes' => $volumesRo]
                ),
                [self::NETWORK_MAGENTO],
                self::$cliDepends
            );
        }

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
        $config = $this->serviceFactory->create(ServiceFactory::SERVICE_CLI, $version, ['command' => 'run-cron']);

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

            $config['environment'] = [
                'CRONTAB' => implode(PHP_EOL, $preparedCronConfig)
            ];
        }

        return $config;
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
            'XDEBUG_CONFIG' => 'remote_host=host.docker.internal remote_connect_back=1',
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
}
