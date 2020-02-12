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
    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $config = new Repository();

        $config->set([
            self::CONFIG_SYNC_ENGINE => 'native',
            self::CRON_ENABLED => false,
            self::VARIABLES => [
                'PHP_MEMORY_LIMIT' => '2048M',
                'UPLOAD_MAX_FILESIZE' => '64M',
                'MAGENTO_ROOT' => self::DIR_MAGENTO,
                # Name of your server in IDE
                'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
                # Docker host for developer environments, can be different for your OS
                'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
            ]
        ]);

        return $config;
    }
}
