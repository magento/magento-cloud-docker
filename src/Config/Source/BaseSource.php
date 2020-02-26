<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Compose\BuilderFactory;

/**
 * The very base source for most of other sources
 */
class BaseSource implements SourceInterface
{
    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $config = new Repository();

        $config->set([
            self::SYSTEM_MODE => BuilderFactory::BUILDER_PRODUCTION,
            self::SYSTEM_SYNC_ENGINE => null,
            self::CRON_ENABLED => false,
        ]);

        return $config;
    }
}
