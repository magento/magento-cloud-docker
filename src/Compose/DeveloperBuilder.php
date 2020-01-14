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
 * Developer compose configuration.
 *
 * @codeCoverageIgnore
 */
class DeveloperBuilder implements BuilderInterface
{
    public const SYNC_ENGINE_DOCKER_SYNC = 'docker-sync';
    public const SYNC_ENGINE_MUTAGEN = 'mutagen';
    public const SYNC_ENGINE_NATIVE = 'native';

    public const KEY_SYNC_ENGINE = 'sync-engine';

    public const VOLUME_MAGENTO_SYNC = 'magento-sync';

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
        self::SYNC_ENGINE_NATIVE
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
     * @var BuilderFactory
     */
    private $builderFactory;

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
     * @param BuilderFactory $builderFactory
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
        BuilderFactory $builderFactory,
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
        $this->builderFactory = $builderFactory;
        $this->resolver = $resolver;
        $this->envReader = $envReader;
        $this->appReader = $appReader;
    }

    /**
     * @inheritDoc
     */
    public function build(Repository $config): Manager
    {
        $manager = $this->builderFactory
            ->create(BuilderFactory::BUILDER_PRODUCTION)
            ->build($config);

        $syncEngine = $config->get(self::KEY_SYNC_ENGINE);
        $syncConfig = [];

        $phpVersion = $config->get(ServiceInterface::NAME_PHP) ?: $this->serviceConfig->getPhpVersion();

        if ($syncEngine === self::SYNC_ENGINE_DOCKER_SYNC) {
            $syncConfig = ['external' => true];
        } elseif ($syncEngine === self::SYNC_ENGINE_MUTAGEN) {
            $syncConfig = [];
        } elseif ($syncEngine === self::SYNC_ENGINE_NATIVE) {
            $syncConfig = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath(),
                    'o' => 'bind'
                ]
            ];
        }



        $manager->setVolumes([
            self::VOLUME_MAGENTO_SYNC => $syncConfig,
            self::VOLUME_MAGENTO_DB => []
        ]);

        /**
         * Gather all services except DB with specific volumes.
         */
        $services = $manager->getServices();
        $volumes = $this->getMagentoVolumes($config);

        $phpExtensions = $this->getPhpExtensions((string)$phpVersion);
        $phpExtensions[] = "xdebug";
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

        /**
         * @var string $sName
         * @var array $sConfig
         */
        foreach ($services as $sName => $sConfig) {
            if (empty($sConfig['volumes'])) {
                continue;
            }

            $manager->updateService($sName, ['volumes' => $volumes]);
        }

        $manager->updateService(self::SERVICE_DB, [
            'volumes' => array_merge(
                $volumes,
                [
                    self::VOLUME_MAGENTO_DB . ':/var/lib/mysql',
                    '.docker/mysql/docker-entrypoint-initdb.d:/docker-entrypoint-initdb.d'
                ]
            )
        ]);

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
     * @inheritDoc
     */
    private function getMagentoVolumes(Repository $config): array
    {
        $target = self::DIR_MAGENTO;

        if ($config->get(self::KEY_SYNC_ENGINE) !== self::SYNC_ENGINE_NATIVE) {
            $target .= ':nocopy';
        }

        return [
            self::VOLUME_MAGENTO_SYNC . ':' . $target
        ];
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

}
