<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;

interface BuilderInterface
{
    public const DIR_MAGENTO = '/app';

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function build(): array;

    /**
     * @param Repository $config
     */
    public function setConfig(Repository $config): void;

    /**
     * @return Repository
     */
    public function getConfig(): Repository;

    /**
     * @return string
     */
    public function getPath(): string;
}
