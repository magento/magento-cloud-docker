<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns Generic service configuration
 */
class Generic implements ServiceBuilderInterface
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var ExtensionResolver
     */
    private $phpExtension;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Converter $converter,
        ExtensionResolver $phpExtension
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_GENERIC;
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
        $variables = [
            'PHP_EXTENSIONS' => implode(' ', $this->phpExtension->get($config)),
        ];

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_MAILHOG)) {
            $variables['SENDMAIL_PATH'] = '/usr/local/bin/mhsendmail --smtp-addr=mailhog:1025';
        }

        return $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion(ServiceInterface::SERVICE_PHP),
            [
                'env_file' => './.docker/config.env',
                'environment' => $this->converter->convert($variables)
            ],
            $config->getServiceImage(ServiceInterface::SERVICE_PHP),
            $config->getCustomRegistry(),
            $config->getServiceImagePattern(ServiceInterface::SERVICE_PHP_CLI)
        );
    }

    /**
     * @inheritDoc
     */
    public function getNetworks(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    public function getDependsOn(Config $config): array
    {
        return [];
    }
}
