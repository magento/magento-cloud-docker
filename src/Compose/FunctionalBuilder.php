<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Docker functional test builder.
 *
 * @codeCoverageIgnore
 */
class FunctionalBuilder implements BuilderInterface
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
     * @var Converter
     */
    private $converter;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ManagerFactory
     */
    private $managerFactory;

    /**
     * @param ServiceFactory $serviceFactory
     * @param FileList $fileList
     * @param Converter $converter
     * @param ManagerFactory $managerFactory
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        FileList $fileList,
        Converter $converter,
        ManagerFactory $managerFactory
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->managerFactory = $managerFactory;
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

        if (!$phpVersion) {
            throw new ConfigurationMismatchException('PHP version not set');
        }

        $dbVersion = $config->getServiceVersion(ServiceInterface::SERVICE_DB);

        if (!$dbVersion) {
            throw new ConfigurationMismatchException('DB version not set');
        }

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $manager->addVolumes([
            self::VOLUME_MAGENTO => [],
            self::VOLUME_MAGENTO_VENDOR => [],
            self::VOLUME_MAGENTO_GENERATED => [],
            self::VOLUME_MAGENTO_VAR => [],
            self::VOLUME_MAGENTO_ETC => [],
            self::VOLUME_MAGENTO_STATIC => [],
            self::VOLUME_MAGENTO_MEDIA => [],
            self::VOLUME_MAGENTO_DB => [],
            'magento-build-var' => [],
            'magento-build-etc' => [],
            'magento-build-static' => [],
            'magento-build-media' => []
        ]);

        $manager->addService(
            self::SERVICE_DB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_DB,
                (string)$dbVersion,
                [
                    'ports' => ['3306:3306'],
                    'volumes' => [
                        self::VOLUME_MAGENTO_DB . ':/var/lib/mysql',
                    ]
                ]
            ),
            [self::NETWORK_MAGENTO],
            []
        );

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_REDIS)) {
            $manager->addService(
                self::SERVICE_REDIS,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_REDIS,
                    $config->getServiceVersion(ServiceInterface::SERVICE_REDIS)
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_ELASTICSEARCH)) {
            $manager->addService(
                self::SERVICE_ELASTICSEARCH,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_ELASTICSEARCH,
                    $config->getServiceVersion(ServiceInterface::SERVICE_ELASTICSEARCH)
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_NODE)) {
            $manager->addService(
                self::SERVICE_NODE,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_NODE,
                    $config->getServiceVersion(ServiceInterface::SERVICE_NODE),
                    [
                        'volumes' => $this->getMagentoVolumes(false)
                    ]
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_RABBITMQ)) {
            $manager->addService(
                self::SERVICE_RABBITMQ,
                $this->serviceFactory->create(
                    ServiceInterface::SERVICE_RABBITMQ,
                    $config->getServiceVersion(ServiceInterface::SERVICE_RABBITMQ)
                ),
                [self::NETWORK_MAGENTO],
                []
            );
        }

        $manager->addService(
            self::SERVICE_FPM,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_PHP_FPM,
                (string)$phpVersion,
                [
                    'volumes' => $this->getMagentoVolumes(true)
                ]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_DB => []]
        );

        $manager->addService(
            self::SERVICE_BUILD,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_PHP_CLI,
                (string)$phpVersion,
                [
                    'volumes' => array_merge(
                        $this->getMagentoBuildVolumes(false),
                        $this->getComposerVolumes()
                    ),
                ]
            ),
            [self::NETWORK_MAGENTO_BUILD],
            []
        );

        $manager->addService(
            self::SERVICE_DEPLOY,
            $this->getCliService((string)$phpVersion, true),
            [self::NETWORK_MAGENTO],
            self::$cliDepends
        );

        $manager->addService(
            self::SERVICE_WEB,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_NGINX,
                $config->getServiceVersion(ServiceInterface::SERVICE_NGINX),
                [
                    'volumes' => $this->getMagentoVolumes(true)
                ]
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_FPM => []]
        );

        $manager->addService(
            self::SERVICE_VARNISH,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_VARNISH,
                $config->getServiceVersion(ServiceInterface::SERVICE_VARNISH)
            ),
            [self::NETWORK_MAGENTO],
            [self::SERVICE_WEB => []]
        );

        $manager->addService(
            self::SERVICE_TLS,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_TLS,
                $config->getServiceVersion(ServiceInterface::SERVICE_TLS),
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

        /**
         * Generic service.
         */
        $phpExtensions = $this->getPhpExtensions((string)$phpVersion);

        $manager->addService(
            self::SERVICE_GENERIC,
            $this->serviceFactory->create(
                ServiceInterface::SERVICE_GENERIC,
                '',
                [
                    'environment' => $this->converter->convert(array_merge(
                        ['PHP_EXTENSIONS' => implode(' ', $phpExtensions)]
                    )),
                    'env_file' => [
                        './.docker/composer.env',
                        './.docker/global.env'
                    ]
                ]
            ),
            [],
            []
        );

        return $manager;
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            '.:/ece-tools',
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
            self::VOLUME_MAGENTO_VAR . ':' . self::DIR_MAGENTO . '/var:delegated',
            self::VOLUME_MAGENTO_ETC . ':' . self::DIR_MAGENTO . '/app/etc:delegated',
            self::VOLUME_MAGENTO_STATIC . ':' . self::DIR_MAGENTO . '/pub/static:delegated',
            self::VOLUME_MAGENTO_MEDIA . ':' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            '.:/ece-tools',
            self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . $flag,
            self::VOLUME_MAGENTO_VENDOR . ':' . self::DIR_MAGENTO . '/vendor' . $flag,
            self::VOLUME_MAGENTO_GENERATED . ':' . self::DIR_MAGENTO . '/generated' . $flag,
            self::VOLUME_MAGENTO_VAR . ':' . self::DIR_MAGENTO . '/var:delegated',
            self::VOLUME_MAGENTO_ETC . ':' . self::DIR_MAGENTO . '/app/etc:delegated',
            'magento-build-static:' . self::DIR_MAGENTO . '/pub/static:delegated',
            'magento-build-media:' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->fileList->getEceToolsCompose();
    }

    /**
     * @inheritDoc
     */
    private function getPhpExtensions(string $phpVersion): array
    {
        return array_unique(array_merge(
            ExtensionResolver::DEFAULT_PHP_EXTENSIONS,
            ['xsl', 'redis'],
            in_array($phpVersion, ['7.0', '7.1']) ? ['mcrypt'] : []
        ));
    }

    /**
     * @param string $version
     * @param bool $isReadOnly
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getCliService(
        string $version,
        bool $isReadOnly
    ): array {
        return $this->serviceFactory->create(
            ServiceInterface::SERVICE_PHP_CLI,
            $version,
            [
                'volumes' => array_merge(
                    $this->getMagentoVolumes($isReadOnly),
                    $this->getComposerVolumes()
                ),
            ]
        );
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
}
