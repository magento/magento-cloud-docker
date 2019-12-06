<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
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
    public const SYNC_ENGINE_NATIVE = 'native';

    public const KEY_SYNC_ENGINE = 'sync-engine';

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

    private $config;

    /**
     * @param BuilderFactory $builderFactory
     * @param FileList $fileList
     */
    public function __construct(BuilderFactory $builderFactory, FileList $fileList)
    {
        $this->builderFactory = $builderFactory;
        $this->fileList = $fileList;
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
                    'device' => $this->getRootPath(),
                    'o' => 'bind'
                ]
            ];
        }

        $manager->cleanVolumes();
        $manager->addVolumes([
            self::VOLUME_MAGENTO_SYNC => $syncConfig,
            self::VOLUME_MAGENTO_DB => []
        ]);

        $manager->updateServices([
            self::SERVICE_BUILD => [
                'volumes' => $this->getMagentoVolumes($config)
            ],
            self::SERVICE_DEPLOY => [
                'volumes' => $this->getMagentoVolumes($config)
            ],
            self::SERVICE_FPM => [
                'volumes' => $this->getMagentoVolumes($config)
            ],
            self::SERVICE_DB => [
                'volumes' => $this->getMagentoVolumes($config)
            ],
            self::SERVICE_WEB => [
                'volumes' => $this->getMagentoVolumes($config)
            ]
        ]);

        if (!$config->get(self::KEY_NO_CRON, false)) {
            $manager->updateService(
                self::SERVICE_CRON,
                ['volumes' => $this->getMagentoVolumes($config)]
            );
        }

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
     * @return string
     */
    private function getRootPath(): string
    {
        /**
         * For Windows we'll define variable in .env file
         *
         * WINDOWS_PWD=//C/www/my-project
         */
        if (stripos(PHP_OS, 'win') === 0) {
            return '${WINDOWS_PWD}';
        }

        return '${PWD}';
    }
}
