<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Service;

use Magento\CloudDocker\Config\Reader;
use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Filesystem\FilesystemException;

/**
 * Retrieve Service versions/configs from Cloud configuration.
 */
class Config
{
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
     * @var Reader
     */
    private $reader;

    /**
     * @param Reader $reader
     */
    public function __construct(Reader $reader)
    {
        $this->reader = $reader;
    }

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
     * @param Repository $customVersions custom version which overwrite values from configuration files
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
    public function getServiceVersion(string $serviceName)
    {
        try {
            $version = $serviceName === ServiceInterface::NAME_PHP
                ? $this->getPhpVersion()
                : $this->reader->read()['services'][$serviceName]['version'] ?? null;

            return $version;
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * Retrieve version of PHP
     *
     * @return string
     * @throws ConfigurationMismatchException when PHP is not configured
     */
    public function getPhpVersion(): string
    {
        try {
            $config = $this->reader->read();
            list($type, $version) = explode(':', $config['type']);
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if ($type !== ServiceInterface::NAME_PHP) {
            throw new ConfigurationMismatchException(sprintf(
                'Type "%s" is not supported',
                $type
            ));
        }

        /**
         * We don't support release candidates.
         */
        return rtrim($version, '-rc');
    }

    /**
     * Retrieves cron configuration.
     *
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getCron(): array
    {
        try {
            return $this->reader->read()['crons'] ?? [];
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getEnabledPhpExtensions(): array
    {
        try {
            return $this->reader->read()['runtime']['extensions'];
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }

    /**
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getDisabledPhpExtensions(): array
    {
        try {
            return $this->reader->read()['runtime']['disabled_extensions'];
        } catch (FilesystemException $exception) {
            throw new ConfigurationMismatchException($exception->getMessage(), $exception->getCode(), $exception);
        }
    }
}
