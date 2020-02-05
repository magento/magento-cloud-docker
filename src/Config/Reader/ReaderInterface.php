<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Reader;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Service\ServiceInterface;

interface ReaderInterface
{
    public const PHP = ServiceInterface::NAME_PHP;
    public const CRONS = 'crons';
    public const SERVICES = 'services';
    public const RUNTIME_EXTENSIONS = 'runtime.extensions';
    public const RUNTIME_DISABLED_EXTENSIONS = 'runtime.disabled_extensions';
    public const MOUNTS = 'mounts';

    /**
     * @return Repository
     *
     * @throws ReaderException
     */
    public function read(): Repository;
}
