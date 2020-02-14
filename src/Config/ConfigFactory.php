<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

/**
 * Creates instances of config repository.
 */
class ConfigFactory
{
    /**
     * @param array $sources
     * @return Config
     */
    public function create(array $sources): Config
    {
        return new Config($sources);
    }
}
