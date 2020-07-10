<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\ProductionBuilder\VolumeResolver;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Config\Source\SourceInterface;
use Magento\CloudDocker\Filesystem\FileList;
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
    /**
     * @var array
     */
    private static $cliDepends = [
        self::SERVICE_DB => [
            'condition' => 'service_healthy'
        ],
        self::SERVICE_REDIS => [
            'condition' => 'service_healthy'
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

    public const SYNC_ENGINE_MOUNT = 'mount';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_MOUNT;

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_NATIVE,
        self::SYNC_ENGINE_MOUNT
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
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param ManagerFactory $managerFactory
     * @param Resolver $resolver
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        ManagerFactory $managerFactory,
        Resolver $resolver,
        VolumeResolver $volumeResolver
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->managerFactory = $managerFactory;
        $this->resolver = $resolver;
        $this->volumeResolver = $volumeResolver;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function build(Config $config): Manager
    {
        $manager = $this->managerFactory->create($config);

        $phpVersion = $config->getServiceVersion(ServiceInterface::SERVICE_PHP);
        $dbVersion = $config->getServiceVersion(ServiceInterface::SERVICE_DB);
        $cliDepends = self::$cliDepends;

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $volumes = [self::VOLUME_MAGENTO => $this->getVolumeConfig()];

        $mounts = $config->getMounts();
        $hasSelenium = $config->hasSelenium();
        $hasTmpMounts = $config->hasTmpMounts();

        $hasGenerated = !version_compare($config->getMagentoVersion(), '2.2.0', '<');

        if ($hasTmpMounts) {
            $volumes[self::VOLUME_DOCKER_MNT] = $this->getVolumeConfig('/.docker/mnt');
        }

        foreach ($this->volumeResolver->getMagentoVolumes(
            $mounts,
            false,
            $hasSelenium,
            $hasGenerated
        ) as $volumeName => $volume) {
            $syncConfig = [];

            if (!empty($volume['volume']) && $config->getSyncEngine() === self::SYNC_ENGINE_NATIVE) {
                $syncConfig = $this->getVolumeConfig($volume['volume']);
            }
            $volumes[$volumeName] = $syncConfig;
        }

        if ($config->getSyncEngine() === self::SYNC_ENGINE_MOUNT) {
            $volumes[self::VOLUME_MAGENTO] = $this->getVolumeConfig();
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_SELENIUM)) {
            $volumes[self::VOLUME_MAGENTO_DEV] = $this->getVolumeConfig('/dev');
        }

        $manager->setVolumes($volumes);

        $volumesBuild = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getDefaultMagentoVolumes(false, $hasGenerated),
            $this->volumeResolver->getComposerVolumes()
        ));
        $volumesRo = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getMagentoVolumes($mounts, true, $hasSelenium, $hasGenerated),
            $this->volumeResolver->getMountVolumes($hasTmpMounts)
        ));
        $volumesRw = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getMagentoVolumes($mounts, false, $hasSelenium, $hasGenerated),
            $this->volumeResolver->getMountVolumes($hasTmpMounts),
            $this->volumeResolver->getComposerVolumes()
        ));
        $volumesMount = $this->volumeResolver->normalize(
            $this->volumeResolver->getMountVolumes($hasTmpMounts)
        );

        $volumePrefix = $config->getNameWithPrefix();

        $manager->addVolume(
            $volumePrefix . self::VOLUME_MARIADB_CONF,
            $this->getVolumeConfig('/.docker/mysql/mariadb.conf.d')
        );

        $this->addDbService($manager, $config, self::SERVICE_DB, $dbVersion, $volumesMount);

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_QUOTE)) {
            $cliDepends = array_merge($cliDepends, [self::SERVICE_DB_QUOTE => ['condition' => 'service_started']]);
            $this->addDbService($manager, $config, self::SERVICE_DB_QUOTE, $dbVersion, $volumesMount);
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_SALES)) {
            $cliDepends = array_merge($cliDepends, [self::SERVICE_DB_SALES => ['condition' => 'service_started']]);
            $this->addDbService($manager, $config, self::SERVICE_DB_SALES, $dbVersion, $volumesMount);
        }

        $esEnvVars = $config->get(SourceInterface::SERVICES_ES_VARS);

        foreach (self::$standaloneServices as $service) {
            if (!$config->hasServiceEnabled($service)) {
                continue;
            }

            switch ($service) {
                case self::SERVICE_REDIS:
                    $serviceConfig = [self::SERVICE_HEALTHCHECK => [
                        'test'=> 'redis-cli ping || exit 1',
                        'interval'=> '30s',
                        'timeout'=> '30s',
                        'retries'=> 3
                    ]];
                    break;

                case self::SERVICE_ELASTICSEARCH:
                    $serviceConfig = !empty($esEnvVars) ? ['environment' => $esEnvVars] : [];
                    break;

                default:
                    $serviceConfig = [];
            }

            $manager->addService(
                $service,
                $this->serviceFactory->create(
                    (string)$service,
                    (string)$config->getServiceVersion($service),
                    $serviceConfig
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        if ($config->hasServiceEnabled(self::SERVICE_NODE)) {
            $manager->addService(
                self::SERVICE_NODE,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_NODE,
                    $config->getServiceVersion(ServiceInterface::SERVICE_NODE),
                    ['volumes' => $volumesRo]
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $manager->addService(
            self::SERVICE_FPM,
            $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_FPM, $phpVersion, ['volumes' => $volumesRo]),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_DB => ['condition' => 'service_healthy']]
        );
        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_NGINX,
                $config->getServiceVersion(ServiceInterface::SERVICE_NGINX),
                [
                    'volumes' => $volumesRo,
                    'environment' => [
                        'VIRTUAL_HOST=' . $config->getHost(),
                        'VIRTUAL_PORT=80',
                        'HTTPS_METHOD=noredirect',
                        'WITH_XDEBUG=' . (int)$config->hasServiceEnabled(ServiceInterface::SERVICE_FPM_XDEBUG)
                    ],
                    'ports' => [
                        $config->getPort() . ':80'
                    ]
                ]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_FPM => []]
        );

        if ($config->hasServiceEnabled(self::SERVICE_VARNISH)) {
            $manager->addService(
                self::SERVICE_VARNISH,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_VARNISH,
                    $config->getServiceVersion(ServiceInterface::SERVICE_VARNISH),
                    [
                        'networks' => [
                            self::NETWORK_MAGENTO => [
                                'aliases' => [$config->getHost()]
                            ]
                        ]
                    ]
                ),
                [],
                [self::SERVICE_WEB => ['condition' => 'service_healthy']]
            );
        }

        $tlsBackendService = $config->hasServiceEnabled(ServiceInterface::SERVICE_VARNISH)
            ? self::SERVICE_VARNISH
            : self::SERVICE_WEB;
        $manager->addService(
            self::SERVICE_TLS,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_TLS,
                $config->getServiceVersion(ServiceInterface::SERVICE_TLS),
                [
                    'environment' => ['HTTPS_UPSTREAM_SERVER_ADDRESS' => $tlsBackendService],
                ]
            ),
            [self::NETWORK_MAGENTO],
            [$tlsBackendService => []]
        );

        if ($config->hasSelenium()) {
            $manager->addService(
                self::SERVICE_SELENIUM,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_SELENIUM,
                    $config->getServiceVersion(ServiceInterface::SERVICE_SELENIUM),
                    [],
                    $config->getServiceImage(ServiceInterface::SERVICE_SELENIUM)
                ),
                [self::NETWORK_MAGENTO],
                [self::SERVICE_WEB => []]
            );
            $manager->addService(
                self::SERVICE_TEST,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_PHP_CLI,
                    $phpVersion,
                    ['volumes' => $volumesRw]
                ),
                [self::NETWORK_MAGENTO],
                $cliDepends
            );
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_BLACKFIRE)) {
            $manager->addService(
                ServiceInterface::SERVICE_BLACKFIRE,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_BLACKFIRE,
                    $config->getServiceVersion(ServiceInterface::SERVICE_BLACKFIRE),
                    [
                        'environment' => [
                            'BLACKFIRE_SERVER_ID' => $config->getBlackfireConfig()['server_id'],
                            'BLACKFIRE_SERVER_TOKEN' => $config->getBlackfireConfig()['server_token'],
                            'BLACKFIRE_CLIENT_ID' => $config->getBlackfireConfig()['client_id'],
                            'BLACKFIRE_CLIENT_TOKEN' => $config->getBlackfireConfig()['client_token']
                        ],
                        'ports' => ["8707"]
                    ]
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $phpExtensions = $this->phpExtension->get($config);

        /**
         * Include Xdebug if --with-xdebug is set
         */
        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_FPM_XDEBUG)) {
            $manager->addService(
                self::SERVICE_FPM_XDEBUG,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_FPM_XDEBUG,
                    $phpVersion,
                    [
                        'volumes' => $volumesRo,
                        'environment' => $this->converter->convert(['PHP_EXTENSIONS' => implode(' ', $phpExtensions)])
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
                ServiceInterface::SERVICE_GENERIC,
                $config->getServiceVersion(self::SERVICE_GENERIC),
                [
                    'env_file' => './.docker/config.env',
                    'environment' => $this->converter->convert(
                        [
                            'PHP_EXTENSIONS' => implode(' ', $phpExtensions),
                        ]
                    )
                ]
            ),
            [],
            []
        );
        $manager->addService(
            self::SERVICE_BUILD,
            $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_CLI, $phpVersion, ['volumes' => $volumesBuild]),
            [self::NETWORK_MAGENTO_BUILD],
            $cliDepends
        );
        $manager->addService(
            self::SERVICE_DEPLOY,
            $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_CLI, $phpVersion, ['volumes' => $volumesRo]),
            [self::NETWORK_MAGENTO],
            self::$cliDepends
        );

        if ($config->hasCron()) {
            $manager->addService(
                self::SERVICE_CRON,
                array_merge(
                    $this->getCronCliService($config),
                    ['volumes' => $volumesRo]
                ),
                [self::NETWORK_MAGENTO],
                $cliDepends
            );
        }

        return $manager;
    }

    /**
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(Config $config): array
    {
        $cron = $this->serviceFactory->create(
            ServiceInterface::SERVICE_PHP_CLI,
            $config->getServiceVersion(ServiceInterface::SERVICE_PHP),
            ['command' => 'run-cron']
        );
        $preparedCronConfig = [];

        foreach ($config->getCronJobs() as $job) {
            $preparedCronConfig[] = sprintf(
                '%s root cd %s && %s >> %s/var/log/cron.log',
                $job['schedule'],
                self::DIR_MAGENTO,
                str_replace('php ', '/usr/local/bin/php ', $job['command']),
                self::DIR_MAGENTO
            );
        }

        $cron['environment'] = [
            'CRONTAB' => implode(PHP_EOL, $preparedCronConfig)
        ];

        return $cron;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->fileList->getMagentoDockerCompose();
    }

    /**
     * @param string $service
     * @param Manager $manager
     * @param string $version
     * @param array $mounts
     * @param Config $config
     * @throws ConfigurationMismatchException
     * @throws GenericException
     */
    private function addDbService(
        Manager $manager,
        Config $config,
        string $service,
        string $version,
        array $mounts
    ): void {
        $volumePrefix = $config->getNameWithPrefix();
        $mounts[] = $volumePrefix . self::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d';
        $commands = [];

        switch ($service) {
            case self::SERVICE_DB:
                $port = $config->getDbPortsExpose();

                $manager->addVolume($volumePrefix . self::VOLUME_MAGENTO_DB, []);
                $manager->addVolume(
                    self::VOLUME_DOCKER_ETRYPOINT,
                    $this->getVolumeConfig('/.docker/mysql/docker-entrypoint-initdb.d')
                );

                $mounts[] = $volumePrefix . self::VOLUME_MAGENTO_DB . ':/var/lib/mysql';
                $mounts[] = self::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d';
                $serviceType = ServiceInterface::SERVICE_DB;

                if ($config->getDbIncrementIncrement() > 1) {
                    $commands[] = '--auto_increment_increment=' . $config->getDbIncrementIncrement();
                }

                if ($config->getDbIncrementOffset() > 1) {
                    $commands[] = '--auto_increment_offset=' . $config->getDbIncrementOffset();
                }

                break;
            case self::SERVICE_DB_QUOTE:
                $port = $config->getDbQuotePortsExpose();

                $manager->addVolume(self::VOLUME_MAGENTO_DB_QUOTE, []);
                $manager->addVolume(
                    self::VOLUME_DOCKER_ETRYPOINT_QUOTE,
                    $this->getVolumeConfig('/.docker/mysql-quote/docker-entrypoint-initdb.d')
                );

                $mounts[] = self::VOLUME_MAGENTO_DB_QUOTE . ':/var/lib/mysql';
                $mounts[] = self::VOLUME_DOCKER_ETRYPOINT_QUOTE . ':/docker-entrypoint-initdb.d';
                $serviceType = ServiceInterface::SERVICE_DB_QUOTE;
                break;
            case self::SERVICE_DB_SALES:
                $port = $config->getDbSalesPortsExpose();

                $manager->addVolume(self::VOLUME_MAGENTO_DB_SALES, []);
                $manager->addVolume(
                    self::VOLUME_DOCKER_ETRYPOINT_SALES,
                    $this->getVolumeConfig(
                        '/.docker/mysql-sales/docker-entrypoint-initdb.d'
                    )
                );

                $mounts[] = self::VOLUME_MAGENTO_DB_SALES . ':/var/lib/mysql';
                $mounts[] = self::VOLUME_DOCKER_ETRYPOINT_SALES . ':/docker-entrypoint-initdb.d';
                $serviceType = ServiceInterface::SERVICE_DB_SALES;
                break;
            default:
                throw new GenericException(sprintf('Configuration for %s service not exist', $service));
        }

        $dbConfig = [
            'ports' => [$port ? "$port:3306" : '3306'],
            'volumes' => $mounts,
             self::SERVICE_HEALTHCHECK => [
                'test'=> 'mysqladmin ping -h localhost',
                'interval'=> '30s',
                'timeout'=> '30s',
                'retries'=> 3
            ],
        ];

        if ($commands) {
            $dbConfig['command'] = implode(' ', $commands);
        }

        $manager->addService(
            $service,
            $this->serviceFactory->create(
                $serviceType,
                $version,
                $dbConfig,
                $config->getServiceImage(ServiceInterface::SERVICE_DB)
            ),
            [self::NETWORK_MAGENTO],
            []
        );
    }

    /**
     * @param string $device
     * @return array
     */
    private function getVolumeConfig(string $device = '/'): array
    {
        return [
            'driver_opts' => [
                'type' => 'none',
                'device' => $this->resolver->getRootPath($device),
                'o' => 'bind'
            ]
        ];
    }
}
