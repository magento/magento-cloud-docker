<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

/**
 * Compose configuration manager
 */
class Manager
{
    public const DEFAULT_HOST = 'magento2.docker';
    public const DEFAULT_PORT = '80';

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
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @param string|null $host
     * @param string|null $port
     */
    public function __construct(string $host = null, string $port = null)
    {
        $this->host = $host;
        $this->port = $port;
    }

    /**
     * @param string $name
     * @param array $extConfig
     * @param array $networks
     * @param array $depends
     */
    public function addService(string $name, array $extConfig, array $networks, array $depends): void
    {
        $hostname = $name . '.' . $this->getHost();

        $config = [
            'hostname' => $hostname,
        ];

        $config = array_replace($config, $extConfig);

        foreach ($networks as $network) {
            $config['networks'][$network] = [
                'aliases' => [$hostname]
            ];
        }

        $this->services[$name] = [
            'config' => $config,
            'depends_on' => $depends,
        ];
    }

    /**
     * @param string $name
     * @param array $extConfig
     */
    public function updateService(string $name, array $extConfig): void
    {
        if (!isset($this->services[$name])) {
            $this->addService($name, $extConfig, [], []);

            return;
        }

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

    /**
     * Returns host value or default if host not set
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->host ?? self::DEFAULT_HOST;
    }

    /**
     * Returns port value or default if port not set
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->port ?? self::DEFAULT_PORT;
    }
}
