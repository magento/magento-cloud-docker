<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

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
    public const SYNC_ENGINE = 'sync-engine';

    public const SERVICE_PHP_CLI = ServiceFactory::SERVICE_CLI_DEV;
    public const SERVICE_PHP_FPM = ServiceFactory::SERVICE_FPM_DEV;

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_DOCKER_SYNC,
        self::SYNC_ENGINE_MUTAGEN,
    ];

    /**
     * @inheritDoc
     */
    public function build(): array
    {
        $compose = parent::build();
        $compose['volumes'] = [
            'magento-sync' => self::SYNC_ENGINE_DOCKER_SYNC === $this->getConfig()[self::SYNC_ENGINE] ? ['external' => true] : []
        ];

        return $compose;
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoBuildVolumes(Repository $config, bool $isReadOnly): array
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
