<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

/**
 * List of services for compose builder
 */
class ServicePool
{
    /**
     * @var array
     */
    private $services;

    /**
     * @param ServiceBuilderInterface[] $services
     */
    public function __construct(array $services)
    {
        $this->services = $services;
    }

    /**
     * Returns list of services
     *
     * @return ServiceBuilderInterface[]
     */
    public function getServices(): array
    {
        return $this->services;
    }
}
