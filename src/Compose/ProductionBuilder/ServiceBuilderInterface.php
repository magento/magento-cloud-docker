<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;

/**
 * Returns service configuration
 */
interface ServiceBuilderInterface
{
    /**
     * Returns name of the service from BuilderInterface
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Returns name of the service from ServiceInterface
     *
     * @return string
     */
    public function getServiceName(): string;

    /**
     * Returns service configuration based on general configuration
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getConfig(Config $config): array;

    /**
     * Returns service networks
     *
     * @return array
     */
    public function getNetworks(): array;

    /**
     * Returns service dependencies
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getDependsOn(Config $config): array;
}
