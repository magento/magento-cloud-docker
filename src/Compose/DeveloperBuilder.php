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

/**
 * Developer compose configuration.
 *
 * @codeCoverageIgnore
 */
class DeveloperBuilder implements BuilderInterface
{
    public const SYNC_ENGINE_DOCKER_SYNC = 'docker-sync';
    public const SYNC_ENGINE_MUTAGEN = 'mutagen';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_NATIVE;

    public const VOLUME_MAGENTO_SYNC = 'magento-sync';

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
        self::SYNC_ENGINE_NATIVE
    ];

    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Resolver
     */
    private $resolver;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var ExtensionResolver
     */
    private $extensionResolver;

    /**
     * @param BuilderFactory $builderFactory
     * @param FileList $fileList
     * @param Resolver $resolver
     * @param Converter $converter
     * @param ExtensionResolver $extensionResolver
     */
    public function __construct(
        BuilderFactory $builderFactory,
        FileList $fileList,
        Resolver $resolver,
        Converter $converter,
        ExtensionResolver $extensionResolver
    ) {
        $this->builderFactory = $builderFactory;
        $this->fileList = $fileList;
        $this->resolver = $resolver;
        $this->converter = $converter;
        $this->extensionResolver = $extensionResolver;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    public function build(Config $config): Manager
    {
        $volumePrefix = $config->getName() . '-';

        $manager = $this->builderFactory
            ->create(BuilderFactory::BUILDER_PRODUCTION)
            ->build($config);

        $syncEngine = $config->getSyncEngine();
        $syncConfig = [];

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

        $volumesList = [
            $volumePrefix . self::VOLUME_MAGENTO_SYNC => $syncConfig,
            $volumePrefix . self::VOLUME_MAGENTO_DB => []
        ];

        if ($config->hasMariaDbConf()) {
            $volumesList[$volumePrefix . self::VOLUME_MARIADB_CONF] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/mariadb.conf.d'),
                    'o' => 'bind',
                ],
            ];
        }

        if ($config->hasDbEntrypoint()) {
            $volumesList[self::VOLUME_DOCKER_ETRYPOINT] = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/docker-entrypoint-initdb.d'),
                    'o' => 'bind'
                ]
            ];
        }

        $manager->setVolumes($volumesList);

        /**
         * Gather all services except DB with specific volumes.
         */
        $services = $manager->getServices();
        $volumes = $this->getMagentoVolumes($config);

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

        $dbVolumes = [
            $volumePrefix . self::VOLUME_MAGENTO_DB . ':/var/lib/mysql'
        ];

        if ($config->hasMariaDbConf()) {
            $dbVolumes[] = $volumePrefix . self::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d';
        }

        if ($config->hasDbEntrypoint()) {
            $dbVolumes[] = self::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d';
        }

        $manager->updateService(self::SERVICE_DB, [
            'volumes' => array_merge(
                $volumes,
                $dbVolumes
            )
        ]);
        $manager->updateService(self::SERVICE_GENERIC, [
            'environment' => $this->converter->convert(array_merge(
                ['MAGENTO_RUN_MODE' => 'developer'],
                ['PHP_EXTENSIONS' => implode(' ', $this->extensionResolver->get($config))]
            ))
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
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function getMagentoVolumes(Config $config): array
    {
        $volumePrefix = $config->getName() . '-';

        if ($config->getSyncEngine() !== self::SYNC_ENGINE_NATIVE) {
            return [
                $volumePrefix . self::VOLUME_MAGENTO_SYNC . ':' . self::DIR_MAGENTO . ':nocopy'
            ];
        }

        return [
            $volumePrefix . self::VOLUME_MAGENTO_SYNC . ':' . self::DIR_MAGENTO . ':delegated',
        ];
    }
}
