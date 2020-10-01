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
use Magento\CloudDocker\Compose\ProductionBuilder\CliDepend;
use Magento\CloudDocker\Compose\ProductionBuilder\ServicePool;
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
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
    public const SYNC_ENGINE_MOUNT = 'mount';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_MOUNT;

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_NATIVE,
        self::SYNC_ENGINE_MOUNT
    ];

    private static $defaultServices = [
        self::SERVICE_GENERIC,
        self::SERVICE_DEPLOY,
        self::SERVICE_BUILD,
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
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     * @var ServicePool
     */
    private $servicePool;
    /**
     * @var Volume
     */
    private $volume;
    /**
     * @var CliDepend
     */
    private $cliDepend;

    /**
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param ManagerFactory $managerFactory
     * @param VolumeResolver $volumeResolver
     * @param ServicePool $servicePool
     * @param Volume $volume
     * @param CliDepend $cliDepend
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        ManagerFactory $managerFactory,
        VolumeResolver $volumeResolver,
        ServicePool $servicePool,
        Volume $volume,
        CliDepend $cliDepend
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->managerFactory = $managerFactory;
        $this->volumeResolver = $volumeResolver;
        $this->servicePool = $servicePool;
        $this->volume = $volume;
        $this->cliDepend = $cliDepend;
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

        foreach ($this->servicePool->getServices() as $service) {
            if ($config->hasServiceEnabled($service->getName()) || in_array($service->getName(), self::$defaultServices)) {
                $manager->addServiceObject($service);
            }
        }


        $phpVersion = $config->getServiceVersion(ServiceInterface::SERVICE_PHP);
        $dbVersion = $config->getServiceVersion(ServiceInterface::SERVICE_DB);
        $cliDepends = $this->cliDepend->getList($config);

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $mounts = $config->getMounts();

        $hasGenerated = !version_compare($config->getMagentoVersion(), '2.2.0', '<');
        $volumes = [];

        foreach (array_keys($this->volumeResolver->getMagentoVolumes(
            $mounts,
            false,
            $hasGenerated
        )) as $volumeName) {
            $volumes[$volumeName] = [];
        }

        $manager->setVolumes($volumes);

        $volumesRo = $this->volume->getRo($config);
        $volumesRw = $this->volume->getRw($config);
        $volumesMount = $this->volume->getMount($config);

        $this->addDbService($manager, $config, self::SERVICE_DB, $dbVersion, $volumesMount);

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_QUOTE)) {
            $this->addDbService($manager, $config, self::SERVICE_DB_QUOTE, $dbVersion, $volumesMount);
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_SALES)) {
            $this->addDbService($manager, $config, self::SERVICE_DB_SALES, $dbVersion, $volumesMount);
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

        $webConfig = [
            'volumes' => $volumesRo,
            'environment' => [
                'WITH_XDEBUG=' . (int)$config->hasServiceEnabled(ServiceInterface::SERVICE_FPM_XDEBUG)
            ]
        ];

        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_NGINX,
                $config->getServiceVersion(ServiceInterface::SERVICE_NGINX),
                $webConfig
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_FPM => []]
        );

        if ($config->hasServiceEnabled(self::SERVICE_VARNISH)) {
            $manager->addService(
                self::SERVICE_VARNISH,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_VARNISH,
                    $config->getServiceVersion(ServiceInterface::SERVICE_VARNISH)
                ),
                [self::NETWORK_MAGENTO],
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
                    'networks' => [
                        self::NETWORK_MAGENTO => [
                            'aliases' => [$config->getHost()]
                        ]
                    ],
                    'environment' => ['UPSTREAM_HOST' => $tlsBackendService],
                    'ports' => [
                        $config->getPort() . ':80',
                        $config->getTlsPort() . ':443'
                    ]
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
            $envVariables = ['PHP_EXTENSIONS' => implode(' ', array_unique(array_merge($phpExtensions, ['xdebug'])))];
            if ($config->get(SourceInterface::SYSTEM_SET_DOCKER_HOST)) {
                $envVariables['SET_DOCKER_HOST'] = true;
            }
            $manager->addService(
                self::SERVICE_FPM_XDEBUG,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_FPM_XDEBUG,
                    $phpVersion,
                    [
                        'volumes' => $volumesRo,
                        'environment' => $this->converter->convert($envVariables)
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

        return $manager;
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
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function addDbService(
        Manager $manager,
        Config $config,
        string $service,
        string $version,
        array $mounts
    ): void {
        $volumePrefix = $config->getNameWithPrefix();

        if ($config->hasMariaDbConf()) {
            $mounts[] = self::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d';
        }

        $commands = [];

        switch ($service) {
            case self::SERVICE_DB:
                $port = $config->getDbPortsExpose();

                $manager->addVolume($volumePrefix . self::VOLUME_MAGENTO_DB, []);

                $mounts[] = $volumePrefix . self::VOLUME_MAGENTO_DB . ':/var/lib/mysql';

                if ($config->hasDbEntrypoint()) {
                    $mounts[] = self::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d';
                }

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

                $mounts[] = self::VOLUME_MAGENTO_DB_QUOTE . ':/var/lib/mysql';
                $mounts[] = self::VOLUME_DOCKER_ETRYPOINT_QUOTE . ':/docker-entrypoint-initdb.d';
                $serviceType = ServiceInterface::SERVICE_DB_QUOTE;
                break;
            case self::SERVICE_DB_SALES:
                $port = $config->getDbSalesPortsExpose();

                $manager->addVolume(self::VOLUME_MAGENTO_DB_SALES, []);

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
                'test' => 'mysqladmin ping -h localhost',
                'interval' => '30s',
                'timeout' => '30s',
                'retries' => 3
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
}
