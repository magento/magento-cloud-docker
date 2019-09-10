<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;

/**
 * General Builder interface.
 */
interface ComposeInterface
{
    /**
     * @param Repository $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function build(Repository $config): array;

    /**
     * @return string
     */
    public function getPath(): string;
}
