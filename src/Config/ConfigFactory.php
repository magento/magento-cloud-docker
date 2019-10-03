<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Illuminate\Config\Repository;
use Illuminate\Contracts\Config\Repository as RepositoryInterface;

/**
 * Creates instances of config repository.
 */
class ConfigFactory
{
    /**
     * Creates instances of Repository.
     *
     * @param array $items The config array
     * @return RepositoryInterface
     */
    public function create(array $items = []): RepositoryInterface
    {
        return new Repository(['items' => $items]);
    }
}
