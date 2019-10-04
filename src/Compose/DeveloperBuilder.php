<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Config\Environment\Reader;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\Config;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 * Developer compose configuration.
 *
 * @codeCoverageIgnore
 */
class DeveloperBuilder extends ProductionBuilder
{
    public const SYNC_ENGINE_DOCKER_SYNC = 'docker-sync';
    public const SYNC_ENGINE_MUTAGEN = 'mutagen';
    public const SYNC_ENGINE_NATIVE = 'native';

    public const KEY_SYNC_ENGINE = 'sync-engine';

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
        self::SYNC_ENGINE_NATIVE
    ];

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Config $serviceConfig
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param Reader $reader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        DirectoryList $directoryList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        Reader $reader
    ) {
        $this->directoryList = $directoryList;

        parent::__construct(
            $serviceFactory,
            $serviceConfig,
            $fileList,
            $directoryList,
            $converter,
            $phpExtension,
            $reader
        );
    }


    /**
     * @inheritDoc
     */
    public function build(): array
    {
        $compose = parent::build();

        $syncEngine = $this->getConfig()->get(self::KEY_SYNC_ENGINE);
        $syncConfig = [];

        if ($syncEngine === self::SYNC_ENGINE_DOCKER_SYNC) {
            $syncConfig = ['external' => true];
        } elseif ($syncEngine === self::SYNC_ENGINE_MUTAGEN) {
            $syncConfig = [];
        } elseif ($syncEngine === self::SYNC_ENGINE_NATIVE) {
            $rootPath = '${PWD}';

            if ($this->getConfig()->get(self::KEY_USE_ABSOLUTE_PATH)) {
                $rootPath = $this->directoryList->getMagentoRoot();
            }

            $syncConfig = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $rootPath,
                    'o' => 'bind'
                ]
            ];
        }

        $compose['volumes'] = [
            'magento-sync' => $syncConfig
        ];

        return $compose;
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        $target = self::DIR_MAGENTO;

        if ($this->getConfig()->get(self::KEY_SYNC_ENGINE) !== self::SYNC_ENGINE_NATIVE) {
            $target .= ':nocopy';
        }

        return [
            'magento-sync:' . $target
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
    {
        return $this->getMagentoVolumes($isReadOnly);
    }

    /**
     * @inheritDoc
     */
    protected function getVariables(): array
    {
        $variables = parent::getVariables();
        $variables['MAGENTO_RUN_MODE'] = 'developer';

        return $variables;
    }
}
