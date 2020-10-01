<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Source\SourceInterface;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 *
 */
class ElasticSearch implements ServiceInterface
{
    private const INSTALLED_PLUGINS = ['analysis-icu', 'analysis-phonetic'];
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    public function __construct(ServiceFactory $serviceFactory)
    {
        $this->serviceFactory = $serviceFactory;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_ELASTICSEARCH;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $esEnvVars = [];

        if (!empty($config->get(SourceInterface::SERVICES_ES_VARS))) {
            $esEnvVars = $config->get(SourceInterface::SERVICES_ES_VARS);
        }

        if (!empty($plugins = $config->get(SourceInterface::SERVICES_ES_PLUGINS)) && is_array($plugins)) {
            $plugins = array_diff($plugins, self::INSTALLED_PLUGINS);
            if (!empty($plugins)) {
                $esEnvVars[] = 'ES_PLUGINS=' . implode(' ', $plugins);
            }
        }

        return $this->serviceFactory->create(
            $this->getName(),
            $config->getServiceVersion($this->getName()),
            !empty($esEnvVars) ? ['environment' => $esEnvVars] : []
        );
    }

    public function getNetworks(): array
    {
        return [BuilderInterface::NETWORK_MAGENTO];
    }

    public function getDependsOn(Config $config): array
    {
        return [];
    }
}
