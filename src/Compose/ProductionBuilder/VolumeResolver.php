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
                'target' => BuilderInterface::TARGET_ROOT,
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
                    'target' => BuilderInterface::TARGET_ROOT . '/dev',
                    'volume' => '/dev',
                    'mode' => 'rw,delegated'
                ]
            ];
        }

        return [];
    }

    /**
     * @param Config $config
     * @param bool $isReadOnly
     * @param bool $hasGenerated
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function getDefaultMagentoVolumes(Config $config, bool $isReadOnly, bool $hasGenerated = true): array
    {
        $mode = $isReadOnly ? 'ro,delegated' : 'rw,delegated';

        $volumes = [
            $this->getVolume(
                $config,
                BuilderInterface::VOLUME_MAGENTO_VENDOR,
                BuilderInterface::VOLUME_MAGENTO_VENDOR
            ) => [
                'target' => BuilderInterface::TARGET_ROOT . '/vendor',
                'volume' => '/vendor',
                'mode' => $mode,
            ]
        ];

        if ($hasGenerated) {
            $volumes[$this->getVolume(
                $config,
                BuilderInterface::VOLUME_MAGENTO_GENERATED,
                BuilderInterface::VOLUME_MAGENTO_GENERATED
            )] = [
                'target' => BuilderInterface::TARGET_ROOT . '/generated',
                'volume' => '/generated',
                'mode' => $mode
            ];
        }

        return $volumes;
    }

    /**
     * @param Config $config
     * @param bool $isReadOnly
     * @param bool $hasGenerated
     * @return string[][]
     * @throws ConfigurationMismatchException
     */
    public function getMagentoVolumes(
        Config $config,
        bool $isReadOnly,
        bool $hasGenerated = true
    ): array {
        $volumes = $this->getDefaultMagentoVolumes($config, $isReadOnly, $hasGenerated);

        foreach ($config->getMounts() as $name => $volumeData) {
            $path = $volumeData['path'];
            $volumes[$this->getVolume($config, $name, $path)] = [
                'target' => BuilderInterface::TARGET_ROOT . '/' . $path,
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
                'target' => '/composer/cache',
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
                    'target' => '/mnt',
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
                $config['target'],
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

    /**
     * @param Config $config
     * @param string $name
     * @param string $path
     * @return string
     * @throws ConfigurationMismatchException
     */
    private function getVolume(Config $config, string $name, string $path): string
    {
        if ($config->getSyncEngine() === BuilderInterface::SYNC_ENGINE_NATIVE) {
            return $config->getRootDirectory() . '/' . $path;
        }

        return $config->getNameWithPrefix() . str_replace('/', '-', $name);
    }
}
