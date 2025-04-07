<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\ProductionBuilder\ServiceBuilderInterface;
use Magento\CloudDocker\Compose\ProductionBuilder\ServicePool;
use Magento\CloudDocker\Compose\ProductionBuilder\VolumeResolver;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Production compose configuration.
 *
 * @codeCoverageIgnore
 */
class ProductionBuilder implements BuilderInterface
{
    public const SYNC_ENGINE_MOUNT = 'mount';
    public const DEFAULT_SYNC_ENGINE = self::SYNC_ENGINE_MOUNT;

    public const SYNC_ENGINES_LIST = [
        self::SYNC_ENGINE_NATIVE,
        self::SYNC_ENGINE_MOUNT
    ];

    /**
     * @var array
     */
    private static $requiredServices = [
        self::SERVICE_GENERIC,
        self::SERVICE_DEPLOY,
        self::SERVICE_BUILD,
        self::SERVICE_WEB,
        self::SERVICE_FPM,
        self::SERVICE_DB,
    ];

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var ManagerFactory
     */
    private $managerFactory;

    /**
     * @var VolumeResolver
     */
    private $volumeResolver;

    /**
     * @var ServicePool
     */
    private $servicePool;

    /**
     * @param FileList $fileList
     * @param ManagerFactory $managerFactory
     * @param VolumeResolver $volumeResolver
     * @param ServicePool $servicePool
     */
    public function __construct(
        FileList $fileList,
        ManagerFactory $managerFactory,
        VolumeResolver $volumeResolver,
        ServicePool $servicePool
    ) {
        $this->fileList = $fileList;
        $this->managerFactory = $managerFactory;
        $this->volumeResolver = $volumeResolver;
        $this->servicePool = $servicePool;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity")
     * @SuppressWarnings("PHPMD.NPathComplexity")
     */
    public function build(Config $config): Manager
    {
        $manager = $this->managerFactory->create($config);

        foreach ($this->servicePool->getServices() as $service) {
            if ($this->hasServiceEnabled($service, $config)
                || in_array($service->getName(), self::$requiredServices)
            ) {
                $manager->addService($service);
            }
        }

        $manager->addNetwork(self::NETWORK_MAGENTO, ['driver' => 'bridge']);
        $manager->addNetwork(self::NETWORK_MAGENTO_BUILD, ['driver' => 'bridge']);

        $hasGenerated = !version_compare($config->getMagentoVersion(), '2.2.0', '<');

        $volumes = [];

        if ($config->getSyncEngine() !== self::SYNC_ENGINE_NATIVE) {
            foreach (array_keys($this->volumeResolver->getMagentoVolumes(
                $config,
                false,
                $hasGenerated
            )) as $volumeName) {
                $volumes[$volumeName] = [];
            }

            $manager->setVolumes($volumes);
        }

        $manager->addVolume($config->getNameWithPrefix() . BuilderInterface::VOLUME_MAGENTO_DB, []);

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_QUOTE)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DB_QUOTE, []);
        }

        if ($config->hasServiceEnabled(ServiceInterface::SERVICE_DB_SALES)) {
            $manager->addVolume(self::VOLUME_MAGENTO_DB_SALES, []);
        }

        return $manager;
    }

    /**
     * @return string
     */
    public function getPath(): string
    {
        return $this->fileList->getMagentoDockerCompose();
    }

    /**
     * Checks the availability of the service
     *
     * @param ServiceBuilderInterface $service
     * @param Config $config
     * @return bool
     * @throws ConfigurationMismatchException
     */
    private function hasServiceEnabled(ServiceBuilderInterface $service, Config $config): bool
    {
        $serviceNames = [
            BuilderInterface::SERVICE_FPM_XDEBUG,
            BuilderInterface::SERVICE_DB_QUOTE,
            BuilderInterface::SERVICE_DB_SALES,
        ];

        return $config->hasServiceEnabled(
            in_array($service->getName(), $serviceNames, true) ? $service->getServiceName() : $service->getName()
        );
    }
}
