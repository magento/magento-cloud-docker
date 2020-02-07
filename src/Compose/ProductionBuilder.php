<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
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
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param ManagerFactory $managerFactory
     * @param Resolver $resolver
     * @param EnvReader $envReader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        ManagerFactory $managerFactory,
        Resolver $resolver,
        EnvReader $envReader
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->managerFactory = $managerFactory;
        $this->resolver = $resolver;
        $this->envReader = $envReader;
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

        $rootPath = $this->resolver->getRootPath();

        $volumes = [
            self::VOLUME_MAGENTO => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath,
                    'o' => 'bind'
                ]
            ],
            self::VOLUME_MAGENTO_DB => []
        ];

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_SELENIUM)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DEV, []);
        }

        if ($this->getMountVolumes($config)) {
            $volumes[self::VOLUME_DOCKER_MNT] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath . '/.docker/mnt',
                    'o' => 'bind'
                ]
            ];
        }

        foreach ($this->getMagentoVolumes($config, false) as $volume) {
            $volumeConfig = explode(':', $volume);
            $volumeName = reset($volumeConfig);

            $volumes[$volumeName] = $volumes[$volumeName] ?? [];
        }

        $manager->addVolumes($volumes);

        $volumesBuild = array_merge(
            $this->getDefaultMagentoVolumes(false),
            $this->getComposerVolumes()
        );
        $volumesRo = array_merge(
            $this->getMagentoVolumes($config, true),
            $this->getMountVolumes($config)
        );
        $volumesRw = array_merge(
            $this->getMagentoVolumes($config, false),
            $this->getMountVolumes($config),
            $this->getComposerVolumes()
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
                            '.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d'
                        ],
                        $this->getMountVolumes($config)
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
                $this->serviceFactory->create(ServiceInterface::SERVICE_PHP_CLI, $phpVersion,
                    ['volumes' => $volumesRw]),
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

    /**
     * @param bool $isReadOnly
     * @return array
     */
    private function getDefaultMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
        ];
    }

    /**
     * @param Repository $config
     * @param bool $isReadOnly
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getMagentoVolumes(Config $config, bool $isReadOnly): array
    {
        $volumes = $this->getDefaultMagentoVolumes($isReadOnly);
        $volumeConfiguration = $config->getMounts();

        foreach (array_keys($volumeConfiguration) as $volume) {
            $volumes[] = sprintf(
                '%s:%s:delegated',
                'magento-' . str_replace('/', '-', $volume),
                self::DIR_MAGENTO . '/' . $volume
            );
        }

        if ($config->hasSelenium()) {
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
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getMountVolumes(Config $config): array
    {
        if ($config->hasTmpMounts()) {
            return [self::VOLUME_DOCKER_MNT . ':/mnt'];
        }

        return [];
    }
}
