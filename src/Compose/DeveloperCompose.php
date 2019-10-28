<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Service\ServiceFactory;

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

    const SERVICE_PHP_CLI = ServiceFactory::SERVICE_CLI_DEV;
    const SERVICE_PHP_FPM = ServiceFactory::SERVICE_FPM_DEV;

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
     * @inheritDoc
     */
    protected function getMagentoBuildVolumes(Repository $config, bool $isReadOnly): array
    {
        $target = self::DIR_MAGENTO;

        if ($config->get(self::SYNC_ENGINE) === self::SYNC_ENGINE_DOCKER_SYNC) {
            $target .= ':nocopy';
        }

        return [
            'magento-sync:' . $target
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(Repository $config, bool $isReadOnly): array
    {
        $target = self::DIR_MAGENTO;

        if ($config->get(self::SYNC_ENGINE) === self::SYNC_ENGINE_DOCKER_SYNC) {
            $target .= ':nocopy';
        }

        return [
            'magento-sync:' . $target
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
