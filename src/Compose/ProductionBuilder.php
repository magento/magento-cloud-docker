<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\ProductionBuilder\VolumeResolver;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
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
     * @var EnvReader
     */
    private $envReader;

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
     * @param EnvReader $envReader
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        ManagerFactory $managerFactory,
        Resolver $resolver,
        EnvReader $envReader,
        VolumeResolver $volumeResolver
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->managerFactory = $managerFactory;
        $this->resolver = $resolver;
        $this->envReader = $envReader;
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
        $manager = $this->managerFactory->create();

        $phpVersion = $config->getServiceVersion(ServiceInterface::SERVICE_PHP);
        $dbVersion = $config->getServiceVersion(ServiceInterface::SERVICE_DB);
        $hostPort = $config->hasDbPortsExpose();
        $dbPorts = $hostPort ? "$hostPort:3306" : '3306';

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $volumes = [
            self::VOLUME_MAGENTO => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath(),
                    'o' => 'bind'
                ]
            ],
            self::VOLUME_MAGENTO_DB => [],
            self::VOLUME_MARIADB_CONF => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/mariadb.conf.d'),
                    'o' => 'bind',
                ],
            ],
        ];

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_SELENIUM)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DEV, []);
        }

        $mounts = $config->getMounts();
        $hasSelenium = $config->hasSelenium();
        $hasTmpMounts = $config->hasTmpMounts();

        if ($hasTmpMounts) {
            $volumes[self::VOLUME_DOCKER_MNT] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mnt'),
                    'o' => 'bind'
                ]
            ];
        }

        foreach ($this->volumeResolver->getMagentoVolumes($mounts, false, $hasSelenium) as $volumeName => $volume) {
            $syncConfig = [];

            if (!empty($volume['volume']) && $config->getSyncEngine() === self::SYNC_ENGINE_NATIVE) {
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

        if ($config->getSyncEngine() === self::SYNC_ENGINE_MOUNT) {
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
            $this->volumeResolver->getMountVolumes($hasTmpMounts)
        ));
        $volumesRw = $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getMagentoVolumes($mounts, false, $hasSelenium),
            $this->volumeResolver->getMountVolumes($hasTmpMounts),
            $this->volumeResolver->getComposerVolumes()
        ));
        $volumesMount = $this->volumeResolver->normalize(
            $this->volumeResolver->getMountVolumes($hasTmpMounts)
        );

        $manager->addService(
            self::SERVICE_DB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_DB,
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
            if (!$config->hasServiceEnabled($service)) {
                continue;
            }

            $manager->addService(
                $service,
                $this->serviceFactory->create((string)$service, (string)$config->getServiceVersion($service)),
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
            [self::SERVICE_DB => []]
        );
        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_NGINX,
                $config->getServiceVersion(ServiceInterface::SERVICE_NGINX),
                ['volumes' => $volumesRo]
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
                                'aliases' => [Manager::DOMAIN]
                            ]
                        ]
                    ]
                ),
                [],
                [self::SERVICE_WEB => []]
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
                self::$cliDepends
            );
        }

        $phpExtensions = $this->phpExtension->get($config);

        /**
         * Generic service.
         */
        $manager->addService(
            self::SERVICE_GENERIC,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_GENERIC,
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
            $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_CLI, $phpVersion, ['volumes' => $volumesBuild]),
            [self::NETWORK_MAGENTO_BUILD],
            self::$cliDepends
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
                    $this->getCronCliService($phpVersion, $config->getCronJobs()),
                    ['volumes' => $volumesRo]
                ),
                [self::NETWORK_MAGENTO],
                self::$cliDepends
            );
        }

        return $manager;
    }

    /**
     * @param string $version
     * @param array $cronConfig
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(string $version, array $cronConfig): array
    {
        $config = $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_CLI, $version, ['command' => 'run-cron']);
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
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getVariables(Config $config): array
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

        if ($config->hasSelenium()) {
            $variables['MFTF_UTILS'] = 1;
        }

        return array_merge($variables, $envConfig);
    }
}
