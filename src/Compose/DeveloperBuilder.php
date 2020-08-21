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
     * @param Converter $converter
     * @param ExtensionResolver $extensionResolver
     */
    public function __construct(
        BuilderFactory $builderFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $extensionResolver
    ) {
        $this->builderFactory = $builderFactory;
        $this->fileList = $fileList;
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
        $volumesList = [
            $volumePrefix . self::VOLUME_MAGENTO_DB => []
        ];

        $volumes = [self::VOLUME_MAGENTO . ':' . self::DIR_MAGENTO . ':delegated'];

        if (in_array($syncEngine, [self::SYNC_ENGINE_MUTAGEN, self::SYNC_ENGINE_DOCKER_SYNC], true)) {
            $volumesList[$volumePrefix . self::VOLUME_MAGENTO_SYNC] = $syncEngine === self::SYNC_ENGINE_DOCKER_SYNC
                ? ['external' => true]
                : [];
            $volumes = [$volumePrefix . self::VOLUME_MAGENTO_SYNC . ':' . self::DIR_MAGENTO . ':nocopy'];
        }

        if ($config->hasMariaDbConf()) {
            $volumesList[$volumePrefix . self::VOLUME_MARIADB_CONF] = [];
        }

        if ($config->hasDbEntrypoint()) {
            $volumesList[self::VOLUME_DOCKER_ETRYPOINT] = [];
        }

        $manager->setVolumes($volumesList);

        /**
         * Gather all services except DB with specific volumes.
         */
        $services = $manager->getServices();

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
}
