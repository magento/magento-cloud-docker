<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Config\Config;

/**
 * Compose configuration manager
 */
class Manager
{
    /**
     * @var string
     */
    private $version = '2.1';

    /**
     * @var array
     */
    private $networks = [];

    /**
     * @var array
     */
    private $services = [];

    /**
     * @var array
     */
    private $volumes = [];

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param ServiceBuilderInterface $service
     * @throws ConfigurationMismatchException
     */
    public function addService(ServiceBuilderInterface $service): void
    {
        $hostname = $service->getName() . '.' . $this->config->getHost();

        $serviceConfig = [
            'hostname' => $hostname,
        ];

        $serviceConfig = array_replace($serviceConfig, $service->getConfig($this->config));

        foreach ($service->getNetworks() as $network) {
            if (!empty($serviceConfig['networks'][$network]['aliases'])) {
                continue;
            }

            $serviceConfig['networks'][$network] = [
                'aliases' => [$hostname]
            ];
        }

        $this->services[$service->getName()] = [
            'config' => $serviceConfig,
            'depends_on' => $service->getDependsOn($this->config),
        ];
    }

    /**
     * @param string $name
     * @param array $extConfig
     */
    public function updateService(string $name, array $extConfig): void
    {
        $this->services[$name]['config'] = array_replace(
            $this->services[$name]['config'],
            $extConfig
        );
    }

    /**
     * @param string $name
     * @param array $config
     */
    public function addVolume(string $name, array $config): void
    {
        $this->volumes[$name] = $config;
    }

    /**
     * @param array $volumes
     */
    public function setVolumes(array $volumes): void
    {
        $this->volumes = $volumes;
    }

    /**
     * @return array
     */
    public function getVolumes(): array
    {
        return $this->volumes;
    }

    /**
     * @return array
     */
    public function getServices(): array
    {
        $preparedServices = [];

        foreach ($this->services as $name => $service) {
            $preparedServices[$name] = $service['config'];

            foreach ($service['depends_on'] as $depName => $depConfig) {
                if (isset($this->services[$depName])) {
                    $depConfig = $depConfig ?: ['condition' => 'service_started'];

                    $preparedServices[$name]['depends_on'][$depName] = $depConfig;
                }
            }
        }

        return $preparedServices;
    }

    /**
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * @param string $name
     * @param array $config
     */
    public function addNetwork(string $name, array $config): void
    {
        $this->networks[$name] = $config;
    }

    /**
     * @param string $version
     */
    public function setVersion(string $version): void
    {
        $this->version = $version;
    }

    /**
     * @return array
     */
    public function getNetworks(): array
    {
        ksort($this->networks);

        return $this->networks;
    }
}
