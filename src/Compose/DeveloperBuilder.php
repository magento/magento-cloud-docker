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
use Magento\CloudDocker\Filesystem\FileList;
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
    public const SYNC_ENGINE_MANUAL_NATIVE = 'manual-native';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_NATIVE;

    public const VOLUME_MAGENTO_SYNC = 'magento-sync';

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
        self::SYNC_ENGINE_NATIVE,
        self::SYNC_ENGINE_MANUAL_NATIVE
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
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     * @param BuilderFactory $builderFactory
     * @param FileList $fileList
     * @param Converter $converter
     * @param ExtensionResolver $extensionResolver
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(
        BuilderFactory $builderFactory,
        FileList $fileList,
        Converter $converter,
        ExtensionResolver $extensionResolver,
        VolumeResolver $volumeResolver
    ) {
        $this->builderFactory = $builderFactory;
        $this->fileList = $fileList;
        $this->converter = $converter;
        $this->extensionResolver = $extensionResolver;
        $this->volumeResolver = $volumeResolver;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     */
    public function build(Config $config): Manager
    {
        $volumePrefix = $config->getNameWithPrefix();

        $manager = $this->builderFactory
            ->create(BuilderFactory::BUILDER_PRODUCTION)
            ->build($config);

        $syncEngine = $config->getSyncEngine();
        $volumesList = [
            $volumePrefix . self::VOLUME_MAGENTO_DB => []
        ];

        if ($syncEngine === self::SYNC_ENGINE_MANUAL_NATIVE) {
            $volumes = [$volumePrefix . ltrim(self::TARGET_ROOT, '/') . ':' . self::TARGET_ROOT];
            $volumesList[$volumePrefix . ltrim(self::TARGET_ROOT, '/')] = [];
        } else {
            $volumes = [$this->volumeResolver->getMagentoVolume($config) . ':' . self::TARGET_ROOT . ':delegated'];
        }

        if (in_array($syncEngine, [self::SYNC_ENGINE_MUTAGEN, self::SYNC_ENGINE_DOCKER_SYNC], true)) {
            $volumesList[$volumePrefix . self::VOLUME_MAGENTO_SYNC] = $syncEngine === self::SYNC_ENGINE_DOCKER_SYNC
                ? ['external' => true]
                : [];
            $volumes = [$volumePrefix . self::VOLUME_MAGENTO_SYNC . ':' . self::TARGET_ROOT . ':nocopy'];
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
            $dbVolumes[] = self::VOLUME_MARIADB_CONF . ':/etc/mysql/mariadb.conf.d';
        }

        if ($config->hasDbEntrypoint()) {
            $dbVolumes[] = self::VOLUME_DOCKER_ENTRYPOINT . ':/docker-entrypoint-initdb.d';
        }

        $manager->updateService(self::SERVICE_DB, [
            'volumes' => array_merge(
                $volumes,
                $dbVolumes
            )
        ]);

        $variables = [
            'MAGENTO_RUN_MODE' => 'developer',
            'PHP_EXTENSIONS' => implode(' ', $this->extensionResolver->get($config))
        ];

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_MAILHOG)) {
            $variables['SENDMAIL_PATH'] = '/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025';
        }

        $manager->updateService(self::SERVICE_GENERIC, [
            'environment' => $this->converter->convert($variables)
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
