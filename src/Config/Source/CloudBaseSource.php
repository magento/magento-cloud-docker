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
            self::SERVICES_SELENIUM_ENABLED => false,
            self::SERVICES_SELENIUM_IMAGE => ServiceInterface::SELENIUM_IMAGE,
            self::SERVICES_SELENIUM_VERSION => ServiceInterface::SELENIUM_VERSION,
            self::SERVICES_NGINX_ENABLED => true,
            self::SERVICES_NGINX_VERSION => ServiceInterface::DEFAULT_NGINX_VERSION,
            self::SERVICES_NGINX_IMAGE => true,
            self::CONFIG_TMP_MOUNTS => true
        ]);

        try {
            $repository->set([
                self::SERVICES_VARNISH_ENABLED => true,
                self::SERVICES_VARNISH_IMAGE => $this->serviceFactory->getImage(
                    ServiceInterface::SERVICE_VARNISH
                ),
                self::SERVICES_VARNISH_VERSION => ServiceInterface::DEFAULT_VARNISH_VERSION,
                self::SERVICES_TLS_ENABLED => true,
                self::SERVICES_TLS_VERSION => ServiceInterface::DEFAULT_TLS_VERSION,
                self::SERVICES_TLS_IMAGE => $this->serviceFactory->getImage(
                    ServiceInterface::SERVICE_TLS
                )
            ]);
        } catch (ConfigurationMismatchException $exception) {
            throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $repository;
    }
}
