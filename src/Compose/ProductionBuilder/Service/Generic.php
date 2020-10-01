<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder\Service;

use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceInterface as BuilderServiceInterface;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 *
 */
class Generic implements BuilderServiceInterface
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
     *
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
    public function getConfig(Config $config): array
    {
        return $this->serviceFactory->create(
            $this->getName(),
            $config->getServiceVersion(BuilderInterface::SERVICE_GENERIC),
            [
                'env_file' => './.docker/config.env',
                'environment' => $this->converter->convert(
                    [
                        'PHP_EXTENSIONS' => implode(' ', $this->phpExtension->get($config)),
                    ]
                )
            ]
        );
    }

    public function getNetworks(): array
    {
        return [];
    }

    public function getDependsOn(Config $config): array
    {
        return [];
    }
}
