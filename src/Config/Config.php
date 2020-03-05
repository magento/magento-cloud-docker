<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Illuminate\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Source\SourceException;
use Magento\CloudDocker\Config\Source\SourceInterface;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Source configuration
 */
class Config
{
    public const DEFAULT_HOST = 'magento2.docker';
    public const DEFAULT_PORT = '80';

    /**
     * @var SourceInterface[]
     */
    private $sources;

    /**
     * @var Repository
     */
    private $data;

    /**
     * @param SourceInterface[] $sources
     */
    public function __construct(array $sources = [])
    {
        $this->sources = $sources;
        $this->data = new Repository();
    }

    /***
     * @return Repository
     * @throws ConfigurationMismatchException
     */
    public function all(): Repository
    {
        if ($this->data->all()) {
            return $this->data;
        }

        $data = [];

        try {
            foreach ($this->sources as $source) {
                $data = array_replace_recursive(
                    $data,
                    $source->read()->all()
                );
            }
        } catch (SourceException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $this->data = new Repository($data);
    }

    /**
     * @param string $key
     * @return mixed
     * @throws ConfigurationMismatchException
     */
    public function get(string $key)
    {
        return $this->all()->get($key);
    }

    /**
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getSyncEngine(): string
    {
        if (!$this->all()->has(SourceInterface::CONFIG_SYNC_ENGINE)) {
            throw new ConfigurationMismatchException('Sync engine is not defined');
        }

        return $this->all()->get(SourceInterface::CONFIG_SYNC_ENGINE);
    }

    /**
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getMode(): string
    {
        if (!$this->all()->has(SourceInterface::CONFIG_MODE)) {
            throw new ConfigurationMismatchException('Mode is not defined');
        }

        return $this->all()->get(SourceInterface::CONFIG_MODE);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getCronJobs(): array
    {
        return $this->all()->get(SourceInterface::CRON_JOBS, []);
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasCron(): bool
    {
        return (bool)$this->all()->get(SourceInterface::CRON_ENABLED);
    }

    /**
     * @param string $name
     * @param string $type
     * @return string
     */
    private function getKey(string $name, string $type): string
    {
        return SourceInterface::SERVICES . '.' . $name . '.' . $type;
    }

    /**
     * @param string $name
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasServiceEnabled(string $name): bool
    {
        $key = $this->getKey($name, 'enabled');

        return (bool)$this->all()->get($key);
    }

    /**
     * @param string $name
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getServiceVersion(string $name): string
    {
        $key = $this->getKey($name, 'version');

        if (!$this->all()->has($key)) {
            throw new ConfigurationMismatchException(sprintf(
                'Service version for %s is not defined',
                $key
            ));
        }

        return $this->all()->get($key);
    }

    /**
     * @param string $name
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getServiceImage(string $name): string
    {
        $key = $this->getKey($name, 'image');

        if (!$this->all()->has($key)) {
            throw new ConfigurationMismatchException('Service image is not defined');
        }

        return $this->all()->get($key);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getMounts(): array
    {
        return $this->all()->get(SourceInterface::MOUNTS, []);
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasTmpMounts(): bool
    {
        return (bool)$this->all()->get(SourceInterface::CONFIG_TMP_MOUNTS);
    }

    /**
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    public function getDbPortsExpose(): ?string
    {
        return $this->all()->get(SourceInterface::SYSTEM_EXPOSE_DB_PORTS);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getEnabledPhpExtensions(): array
    {
        return $this->all()->get(SourceInterface::PHP_EXTENSIONS, []);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getDisabledPhpExtensions(): array
    {
        return $this->all()->get(SourceInterface::PHP_DISABLED_EXTENSIONS, []);
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasSelenium(): bool
    {
        return $this->hasServiceEnabled(ServiceInterface::SERVICE_SELENIUM);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getVariables(): array
    {
        $config = $this->all();

        if ($this->hasSelenium()) {
            $config->set(SourceInterface::VARIABLES . '.' . 'MFTF_UTILS', 1);
        }

        return $config->get(SourceInterface::VARIABLES);
    }

    /**
     * Returns host value or default if host not set
     *
     * @return string
     */
    public function getHost(): string
    {
        return $this->get(SourceInterface::CONFIG_HOST) ?? self::DEFAULT_HOST;
    }

    /**
     * Returns port value or default if port not set
     *
     * @return string
     */
    public function getPort(): string
    {
        return $this->get(SourceInterface::CONFIG_PORT) ?? self::DEFAULT_PORT;
    }
}
