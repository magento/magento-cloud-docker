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
     * @var Reader
     */
    private $reader;

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
     * @param Reader $reader
     * @param ManagerFactory $managerFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        Reader $reader,
        ManagerFactory $managerFactory,
        Resolver $resolver
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->serviceConfig = $serviceConfig;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
        $this->reader = $reader;
        $this->managerFactory = $managerFactory;
        $this->resolver = $resolver;
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

        $manager->addVolume(self::VOLUME_MAGENTO, [
            'driver_opts' => [
                'type' => 'none',
                'device' => $rootPath,
                'o' => 'bind'
            ]
        ]);
        $manager->addVolumes([
            self::VOLUME_MAGENTO_VENDOR => [],
            self::VOLUME_MAGENTO_GENERATED => [],
            self::VOLUME_MAGENTO_VAR => [],
            self::VOLUME_MAGENTO_ETC => [],
            self::VOLUME_MAGENTO_STATIC => [],
            self::VOLUME_MAGENTO_MEDIA => [],
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
                        $this->getDockerMount($config),
                        [
                            self::VOLUME_MAGENTO_DB . ':/var/lib/mysql',
                            '.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d'
                        ]
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
                    [
                        'volumes' => $this->getMagentoVolumes($config, false)
                    ]
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
                [
                    'volumes' => $this->getMagentoVolumes($config, true)
                ]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_DB => []]
        );
        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceFactory::SERVICE_NGINX,
                $config->get(ServiceInterface::NAME_NGINX, self::DEFAULT_NGINX_VERSION),
                [
                    'volumes' => $this->getMagentoVolumes($config, true)
                ]
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
                    $config->get(ServiceFactory::SERVICE_SELENIUM_IMAGE)
                ),
                [self::NETWORK_MAGENTO],
                [self::SERVICE_WEB => []]
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
                [
                    'volumes' => array_merge(
                        $this->getMagentoBuildVolumes($config, false),
                        $this->getComposerVolumes()
                    ),
                ]
            ),
            [self::NETWORK_MAGENTO_BUILD],
            self::$cliDepends
        );
        $manager->addService(
            self::SERVICE_DEPLOY,
            $this->getCliService($config, $phpVersion, true),
            [self::NETWORK_MAGENTO],
            self::$cliDepends
        );

        if (!$config->get(self::KEY_NO_CRON, false)) {
            $manager->addService(
                self::SERVICE_CRON,
                $this->getCronCliService($config, $phpVersion, true),
                [],
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
     * @param Repository $config
     * @param string $version
     * @param bool $isReadOnly
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCronCliService(
        Repository $config,
        string $version,
        bool $isReadOnly
    ): array {
        $cliConfig = $this->getCliService($config, $version, $isReadOnly);

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
     * @param Repository $config
     * @param string $version
     * @param bool $isReadOnly
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(
        Repository $config,
        string $version,
        bool $isReadOnly
    ): array {
        return $this->serviceFactory->create(
            ServiceFactory::SERVICE_CLI,
            $version,
            [
                'volumes' => array_merge(
                    $this->getMagentoVolumes($config, $isReadOnly),
                    $this->getComposerVolumes(),
                    $this->getDockerMount($config)
                ),
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
     * @param bool $isReadOnly
     * @return array
     */
    private function getMagentoVolumes(Repository $config, bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        $volumes = [
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
            self::VOLUME_MAGENTO_VAR . ':' . self::DIR_MAGENTO . '/var:delegated',
            self::VOLUME_MAGENTO_ETC . ':' . self::DIR_MAGENTO . '/app/etc:delegated',
            self::VOLUME_MAGENTO_STATIC . ':' . self::DIR_MAGENTO . '/pub/static:delegated',
            self::VOLUME_MAGENTO_MEDIA . ':' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];

        if ($this->hasSelenium($config)) {
            $volumes[] = self::VOLUME_MAGENTO_DEV . ':' . self::DIR_MAGENTO . '/dev:delegated';
        }

        return $volumes;
    }

    /**
     * @param Repository $config
     * @param bool $isReadOnly
     * @return array
     */
    private function getMagentoBuildVolumes(Repository $config, bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        $volumes = [
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
        ];

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
