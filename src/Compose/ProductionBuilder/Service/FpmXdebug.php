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
use Magento\CloudDocker\Compose\ProductionBuilder\Volume;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Config\Source\SourceInterface;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns FpmXdebug service configuration
 */
class FpmXdebug implements ServiceBuilderInterface
{
    /**
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var Volume
     */
    private $volume;

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
     * @param Volume $volume
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Volume $volume,
        Converter $converter,
        ExtensionResolver $phpExtension
    ) {
        $this->serviceFactory = $serviceFactory;
        $this->volume = $volume;
        $this->converter = $converter;
        $this->phpExtension = $phpExtension;
    }

    /**
     * @inheritDoc
     */
    public function getName(): string
    {
        return BuilderInterface::SERVICE_FPM_XDEBUG;
    }

    /**
     * @inheritDoc
     */
    public function getServiceName(): string
    {
        return ServiceInterface::SERVICE_FPM_XDEBUG;
    }

    /**
     * @inheritDoc
     */
    public function getConfig(Config $config): array
    {
        $envVariables = [
            'PHP_EXTENSIONS' => implode(' ', array_unique(array_merge($this->phpExtension->get($config), ['xdebug'])))
        ];
        if ($config->get(SourceInterface::SYSTEM_SET_DOCKER_HOST)) {
            $envVariables['SET_DOCKER_HOST'] = true;
        }

        return $this->serviceFactory->create(
            $this->getServiceName(),
            $config->getServiceVersion(ServiceInterface::SERVICE_PHP),
            [
                'volumes' => $this->volume->getRo($config),
                'environment' => $this->converter->convert($envVariables)
            ]
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
        return [BuilderInterface::SERVICE_DB => []];
    }
}
