<?php

/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

use Magento\CloudDocker\Config\Config;

class Volume
{
    /**
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     *
     * @param VolumeResolver $volumeResolver
     */
    public function __construct(VolumeResolver $volumeResolver)
    {
        $this->volumeResolver = $volumeResolver;
    }

    /**
     * @param Config $config
     * @return array
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    public function getRo(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume(true),
            $this->volumeResolver->getDevVolumes($config->hasSelenium()),
            $this->volumeResolver->getMagentoVolumes($config->getMounts(), true, $this->hasGenerated($config)),
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts())
        ));
    }

    public function getRw(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume(false),
            $this->volumeResolver->getDevVolumes($config->hasSelenium()),
            $this->volumeResolver->getMagentoVolumes($config->getMounts(), false, $this->hasGenerated($config)),
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts()),
            $this->volumeResolver->getComposerVolumes()
        ));
    }

    public function getBuild(Config $config): array
    {
        return $this->volumeResolver->normalize(array_merge(
            $this->volumeResolver->getRootVolume(false),
            $this->volumeResolver->getDefaultMagentoVolumes(false, $this->hasGenerated($config)),
            $this->volumeResolver->getComposerVolumes()
        ));
    }

    public function getMount(Config $config): array
    {
        return $this->volumeResolver->normalize(
            $this->volumeResolver->getMountVolumes($config->hasTmpMounts())
        );
    }

    private function hasGenerated(Config $config): bool
    {
        return !version_compare($config->getMagentoVersion(), '2.2.0', '<');
    }
}
