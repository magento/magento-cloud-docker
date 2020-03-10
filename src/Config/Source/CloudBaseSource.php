<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Base predefined set for Magento Cloud
 */
class CloudBaseSource implements SourceInterface
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var array
     */
    private static $services = [
        ServiceInterface::SERVICE_SELENIUM => false,
        ServiceInterface::SERVICE_NGINX => true,
        ServiceInterface::SERVICE_TLS => true,
        ServiceInterface::SERVICE_VARNISH => true,
        ServiceInterface::SERVICE_GENERIC => true
    ];

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
    public function read(): Repository
    {
        $repository = new Repository();

        $repository->set([
            self::SYSTEM_TMP_MOUNTS => true,
            self::VARIABLES => [
                'PHP_MEMORY_LIMIT' => '2048M',
                'UPLOAD_MAX_FILESIZE' => '64M',
                'MAGENTO_ROOT' => self::DIR_MAGENTO,
                # Name of your server in IDE
                'PHP_IDE_CONFIG' => 'serverName=magento_cloud_docker',
                # Docker host for developer environments, can be different for your OS
                'XDEBUG_CONFIG' => 'remote_host=host.docker.internal',
            ]
        ]);

        try {
            foreach (self::$services as $service => $status) {
                $path = self::SERVICES . '.' . $service . '.';

                $repository->set([
                    $path . 'enabled' => $status,
                    $path . 'image' => $this->serviceFactory->getDefaultImage($service),
                    $path . 'version' => $this->serviceFactory->getDefaultVersion($service)
                ]);
            }
        } catch (ConfigurationMismatchException $exception) {
            throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $repository;
    }
}
