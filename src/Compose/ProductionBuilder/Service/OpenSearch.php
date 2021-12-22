<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Source\SourceInterface;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 * Returns OpenSearch service configuration
 */
class OpenSearch implements ServiceBuilderInterface
{
    private const INSTALLED_PLUGINS = ['analysis-icu', 'analysis-phonetic'];

    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_OPENSEARCH;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return $this->getName();
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $osEnvVars = [
            'cluster.name=docker-cluster',
            'discovery.type=single-node',
            'discovery.seed_hosts=opensearch',
            'bootstrap.memory_lock=true',
            'DISABLE_SECURITY_PLUGIN=true',
        ];

        if (!empty($config->get(SourceInterface::SERVICES_OS_VARS))) {
            $osEnvVars = array_merge($osEnvVars, $config->get(SourceInterface::SERVICES_OS_VARS));
        }

        if (!empty($plugins = $config->get(SourceInterface::SERVICES_OS_PLUGINS)) && is_array($plugins)) {
            $plugins = array_diff($plugins, self::INSTALLED_PLUGINS);
            if (!empty($plugins)) {
                $osEnvVars[] = 'OS_PLUGINS=' . implode(' ', $plugins);
            }
        }

        return $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion($this->getServiceName()),
            !empty($osEnvVars) ? ['environment' => $osEnvVars] : [],
            $config->getServiceImage($this->getServiceName()),
            $config->getCustomRegistry(),
            $config->getServiceImagePattern($this->getServiceName())
        );
    }

    /**
     * @inheritDoc
     */
    public function getNetworks(): array
    {
        return [BuilderInterface::NETWORK_MAGENTO];
    }

    /**
     * @inheritDoc
     */
    public function getDependsOn(Config $config): array
    {
        return [];
    }
}
