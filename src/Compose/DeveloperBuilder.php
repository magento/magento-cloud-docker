<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

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
            $syncConfig = [
                'driver_opts' => [
                    'type' => 'none',
                    'device' => $this->getRootPath(),
                    'o' => 'bind'
                ]
            ];
        }

        $compose['volumes'] = [
            'magento-sync' => $syncConfig,
            'magento-db' => []
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

    /**
     * @inheritDoc
     */
    protected function getDockerMount(): array
    {
        return [];
    }
}
