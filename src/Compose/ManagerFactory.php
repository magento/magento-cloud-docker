<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

/**
 * Creates instance of Manager
 *
 * @see Manager
 */
class ManagerFactory
{
    public function create(): Manager
    {
        return new Manager();
    }
}
