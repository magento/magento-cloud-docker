<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Compose;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Filesystem\FilesystemException;

interface ReaderInterface
{
    public const TYPE = 'type';
    public const CRONS = 'crons';
    public const SERVICES = 'services';
    public const RUNTIME_EXTENSIONS = 'runtime.extensions';
    public const RUNTIME_DISABLED_EXTENSIONS = 'runtime.disabled_extensions';
    public const MOUNTS = 'mounts';

    /**
     * @return Repository
     *
     * @throws FilesystemException
     */
    public function read(): Repository;
}
