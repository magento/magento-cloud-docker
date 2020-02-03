<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Compose\ProductionBuilder\VolumeResolver;
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
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     * @param BuilderFactory $builderFactory
     * @param FileList $fileList
     * @param Resolver $resolver
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(
        BuilderFactory $builderFactory,
        FileList $fileList,
        Resolver $resolver,
        VolumeResolver $volumeResolver
    ) {
        $this->builderFactory = $builderFactory;
        $this->fileList = $fileList;
        $this->resolver = $resolver;
        $this->volumeResolver = $volumeResolver;
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
            self::VOLUME_MAGENTO_DB => [],
            self::VOLUME_DOCKER_ETRYPOINT => [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->resolver->getRootPath('/.docker/mysql/docker-entrypoint-initdb.d'),
                    'o' => 'bind'
                ]
            ]
        ]);

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

        $manager->updateService(self::SERVICE_DB, [
            'volumes' => array_merge(
                $volumes,
                [
                    self::VOLUME_MAGENTO_DB . ':/var/lib/mysql',
                    self::VOLUME_DOCKER_ETRYPOINT . ':/docker-entrypoint-initdb.d'
                ]
            )
        ]);
        $manager->updateService(self::SERVICE_BUILD, [
            'volumes' => array_merge(
                $volumes,
                $this->volumeResolver->normalize(
                    $this->volumeResolver->getComposerVolumes()
                )
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
            self::VOLUME_MAGENTO_SYNC . ':' . $target . ':delegated',
        ];
    }
}
