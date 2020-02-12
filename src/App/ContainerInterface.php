<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\App;

/**
 * Interface for DI container
 */
interface ContainerInterface extends \Psr\Container\ContainerInterface
{
    /**
     * Create an object
     *
     * @param string $abstract
     * @param array $params
     * @return mixed
     */
    public function create(string $abstract, array $params = []);
}
