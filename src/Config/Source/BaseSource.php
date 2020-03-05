<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;

/**
 * The very base source for most of other sources
 */
class BaseSource implements SourceInterface
{
    public const DEFAULT_HOST = 'magento2.docker';
    public const DEFAULT_PORT = '80';

    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $config = new Repository();

        $config->set([
            self::CONFIG_SYNC_ENGINE => 'native',
            self::CRON_ENABLED => false,
        ]);

        return $config;
    }
}
