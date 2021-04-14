<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceInterface;
use Magento\CloudDocker\App\ConfigurationMismatchException;

/**
 * Returns volumes configuration
 */
class Volume
{
    /**
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(VolumeResolver $volumeResolver)
    {
        $this->volumeResolver = $volumeResolver;
    }

    /**
     * Returns configuration of read-only volumes
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getRo(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume($config, true),
            $this->volumeResolver->getDevVolumes($config, $config->hasServiceEnabled(ServiceInterface::SERVICE_TEST)),
            $this->volumeResolver->getMagentoVolumes($config, true, $this->hasGenerated($config)),
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts())
        ));
    }

    /**
     * Returns configuration of volumes with write permission
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getRw(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume($config, false),
            $this->volumeResolver->getDevVolumes($config, $config->hasServiceEnabled(ServiceInterface::SERVICE_TEST)),
            $this->volumeResolver->getMagentoVolumes($config, false, $this->hasGenerated($config)),
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts()),
            $this->volumeResolver->getComposerVolumes()
        ));
    }

    /**
     * Returns configuration of volumes for build stage
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getBuild(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume($config, false),
            $this->volumeResolver->getDefaultMagentoVolumes($config, false, $this->hasGenerated($config)),
            $this->volumeResolver->getComposerVolumes()
        ));
    }

    /**
     * Returns base mount configuration
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getMount(Config $config): array
    {
        return $this->volumeResolver->normalize(
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts())
        );
    }

    /**
     * Check that current magento version has generated folder
     *
     * @param Config $config
     * @return bool
     * @throws ConfigurationMismatchException
     */
    private function hasGenerated(Config $config): bool
    {
        return !version_compare($config->getMagentoVersion(), '2.2.0', '<');
    }
}
