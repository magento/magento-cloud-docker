<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Magento\CloudDocker\Filesystem\FilesystemException;

/**
 * Read content of file.
 */
interface ReaderInterface
{
    /**
     * @return array
     * @throws FilesystemException
     */
    public function read(): array;
}
