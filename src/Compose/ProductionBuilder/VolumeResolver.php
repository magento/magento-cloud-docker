<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\ProductionBuilder;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\BuilderInterface;
use Magento\CloudDocker\Config\Config;

/**
 * Resolves the volume definitions.
 */
class VolumeResolver
{
    /**
     * @param Config $config
     * @param bool $isReadOnly
     * @return array[]
     */
    public function getRootVolume(Config $config, bool $isReadOnly): array
    {
        $mode = $isReadOnly ? 'ro,delegated' : 'rw,delegated';

        return [
            $this->getMagentoVolume($config) => [
                'path' => BuilderInterface::DIR_MAGENTO,
                'volume' => '/',
                'mode' => $mode
            ]
        ];
    }

    /**
     * @param Config $config
     * @param bool $hasTest
     * @return array
     */
    public function getDevVolumes(Config $config, bool $hasTest): array
    {
        if ($hasTest) {
            return [
                $this->getMagentoDevVolume($config) => [
                    'path' => BuilderInterface::DIR_MAGENTO . '/dev',
                    'volume' => '/dev',
                    'mode' => 'rw,delegated'
                ]
            ];
        }

        return [];
    }

    /**
     * @param bool $isReadOnly
     * @param bool $hasGenerated
     * @return array
     */
    public function getDefaultMagentoVolumes(bool $isReadOnly, bool $hasGenerated = true): array
    {
        $mode = $isReadOnly ? 'ro,delegated' : 'rw,delegated';

        $volumes = [
            BuilderInterface::VOLUME_MAGENTO_VENDOR => [
                'path' => BuilderInterface::DIR_MAGENTO . '/vendor',
                'volume' => '/vendor',
                'mode' => $mode,
            ]
        ];

        if ($hasGenerated) {
            $volumes[BuilderInterface::VOLUME_MAGENTO_GENERATED] = [
                'path' => BuilderInterface::DIR_MAGENTO . '/generated',
                'volume' => '/generated',
                'mode' => $mode
            ];
        }

        return $volumes;
    }

    /**
     * @param array $mounts
     * @param bool $isReadOnly
     * @param bool $hasGenerated
     * @return array
     */
    public function getMagentoVolumes(
        array $mounts,
        bool $isReadOnly,
        bool $hasGenerated = true
    ): array {
        $volumes = $this->getDefaultMagentoVolumes($isReadOnly, $hasGenerated);

        foreach ($mounts as $volumeData) {
            $path = $volumeData['path'];
            $volumeName = 'magento-' . str_replace('/', '-', $path);

            $volumes[$volumeName] = [
                'path' => BuilderInterface::DIR_MAGENTO . '/' . $path,
                'volume' => '/' . $path,
                'mode' => 'rw,delegated'
            ];
        }

        return $volumes;
    }

    /**
     * @return array
     */
    public function getComposerVolumes(): array
    {
        return [
            '~/.composer/cache' => [
                'path' => '/root/.composer/cache',
                'mode' => 'rw,delegated'
            ]
        ];
    }

    /**
     * @param bool $hasTmpMounts
     * @return array
     */
    public function getMountVolumes(bool $hasTmpMounts): array
    {
        if ($hasTmpMounts) {
            return [
                BuilderInterface::VOLUME_DOCKER_MNT => [
                    'path' => '/mnt',
                    'mode' => 'rw,delegated'
                ],
            ];
        }

        return [];
    }

    /**
     * Normalize to name:path:mode format
     *
     * @param array $volumes
     * @return array
     */
    public function normalize(array $volumes): array
    {
        $normalized = [];

        foreach ($volumes as $name => $config) {
            $normalized[] = sprintf(
                '%s:%s:%s',
                $name,
                $config['path'],
                $config['mode'] ?? 'rw,delegated'
            );
        }

        return $normalized;
    }

    /**
     * @param Config $config
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getMagentoVolume(Config $config): string
    {
        return $config->getRootDirectory();
    }

    /**
     * @param Config $config
     * @return string
     * @throws ConfigurationMismatchException
     */
    public function getMagentoDevVolume(Config $config): string
    {
        return $config->getRootDirectory() . '/dev';
    }
}
