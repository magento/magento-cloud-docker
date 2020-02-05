<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Illuminate\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Reader\CliReader;
use Magento\CloudDocker\Config\Reader\CloudReader;
use Magento\CloudDocker\Config\Reader\ReaderException;
use Magento\CloudDocker\Config\Reader\ReaderInterface;
use Magento\CloudDocker\Service\ServiceInterface;

class Config
{
    /**
     * @var ReaderInterface
     */
    private $readers;

    /**
     * @param ReaderInterface[] $readers
     */
    public function __construct(array $readers = [])
    {
        $this->readers = $readers;
    }

    /***
     * @return Repository
     * @throws ConfigurationMismatchException
     */
    public function all(): Repository
    {
        $data = [];

        try {
            foreach ($this->readers as $reader) {
                $data = array_replace($data, $reader->read()->all());
            }
        } catch (ReaderException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return new Repository($data);
    }

    /**
     * @return mixed
     * @throws ConfigurationMismatchException
     */
    public function getPhpVersion()
    {
        return $this->all()->get(ReaderInterface::PHP);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getCron(): array
    {
        return $this->all()->get(ReaderInterface::CRONS) ?? [];
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasCron(): bool
    {
        return (bool)$this->getCron();
    }

    /**
     * List of services which can be configured in Cloud docker
     *
     * @var array
     */
    private static $configurableServices = [
        ServiceInterface::NAME_PHP => 'php',
        ServiceInterface::NAME_DB => 'mysql',
        ServiceInterface::NAME_NGINX => 'nginx',
        ServiceInterface::NAME_REDIS => 'redis',
        ServiceInterface::NAME_ELASTICSEARCH => 'elasticsearch',
        ServiceInterface::NAME_RABBITMQ => 'rabbitmq',
        ServiceInterface::NAME_NODE => 'node'
    ];

    /**
     * @var CloudReader
     */
    private $reader;

    /**
     * Retrieves service versions set in configuration files.
     * Returns null if neither of services is configured or provided in $customVersions.
     *
     * Example of return:
     *
     * ```php
     *  [
     *      'elasticsearch' => '5.6',
     *      'db' => '10.0'
     *  ];
     * ```
     *
     * @param \Illuminate\Contracts\Config\Repository $customVersions custom version which overwrite values from
     *     configuration files
     * @return array List of services
     * @throws ConfigurationMismatchException
     */
    public function getAllServiceVersions(Repository $customVersions): array
    {
        $configuredVersions = [];

        foreach (self::$configurableServices as $serviceName) {
            $version = $customVersions->get($serviceName) ?: $this->getServiceVersion($serviceName);
            if ($version) {
                $configuredVersions[$serviceName] = $version;
            }
        }

        return $configuredVersions;
    }

    /**
     * Retrieves service version set in configuration files.
     * Returns null if service was not configured.
     *
     * @param string $serviceName Name of service version need to retrieve
     * @return string|null
     * @throws ConfigurationMismatchException
     */
    public function getServiceVersion(string $serviceName): ?string
    {
        return $serviceName === ServiceInterface::NAME_PHP
            ? $this->getPhpVersion()
            : $this->all()->get('services.' . $serviceName . '.version');
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getMounts(): array
    {
        return $this->all()->get(ReaderInterface::MOUNTS, []);
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasTmpMounts(): bool
    {
        return $this->all()->get(CliReader::OPTION_NO_TMP_MOUNTS) ? false : true;
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasSelenium(): bool
    {
        $config = $this->all();

        return $config->get(CliReader::OPTION_WITH_SELENIUM)
            || $config->get(CliReader::OPTION_SELENIUM_IMAGE)
            || $config->get(CliReader::OPTION_SELENIUM_VERSION);
    }

    /**
     * @return bool
     * @throws ConfigurationMismatchException
     */
    public function hasDbPortsExpose(): bool
    {
        return (bool)$this->all()->get(CliReader::OPTION_EXPOSE_DB_PORT);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getEnabledPhpExtensions(): array
    {
        return $this->all()->get(ReaderInterface::RUNTIME_EXTENSIONS);
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getDisabledPhpExtensions(): array
    {
        return $this->all()->get(ReaderInterface::RUNTIME_DISABLED_EXTENSIONS);
    }
}
