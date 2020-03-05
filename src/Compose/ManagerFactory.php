<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Source\SourceInterface;

/**
 * Creates instance of Manager
 *
 * @see Manager
 */
class ManagerFactory
{
    /**
     * Creates instance of Manager
     *
     * @param Config $config
     * @return Manager
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    public function create(Config $config): Manager
    {
        return new Manager($config);
    }
}
