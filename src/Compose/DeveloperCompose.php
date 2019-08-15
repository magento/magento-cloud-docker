<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;

/**
 * Developer compose configuration.
 *
 * @codeCoverageIgnore
 */
class DeveloperCompose extends ProductionCompose
{
    const SYNC_ENGINE_DOCKER_SYNC = 'docker-sync';
    const SYNC_ENGINE_MUTAGEN = 'mutagen';
    const SYNC_ENGINE = 'sync-engine';

    const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
    ];

    /**
     * @inheritDoc
     */
    public function build(Repository $config): array
    {
        $compose = parent::build($config);
        $compose['volumes'] = [
            'magento-sync' => self::SYNC_ENGINE_DOCKER_SYNC === $config[self::SYNC_ENGINE] ? ['external' => true] : []
        ];

        return $compose;
    }

    /**
     * @param bool $isReadOnly
     * @return array
     */
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
    {
        return [
            'magento-sync:' . self::DIR_MAGENTO . ':nocopy'
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
    {
        return [
            'magento-sync:' . self::DIR_MAGENTO . ':nocopy'
        ];
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
