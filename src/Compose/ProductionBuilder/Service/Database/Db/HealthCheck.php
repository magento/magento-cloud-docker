<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service\Database\Db;

/**
 * Returns health check configuration of database containers
 */
class HealthCheck
{
    /**
     * Returns health check configuration of database containers
     *
     * @return array
     */
    public function getConfig(): array
    {
        return [
            'test' => [
                'CMD-SHELL',
                '(mariadb-admin ping -h localhost -pmagento2 || mysqladmin ping -h localhost -pmagento2)'
            ],
            'interval' => '30s',
            'timeout' => '30s',
            'retries' => 3
        ];
    }
}
