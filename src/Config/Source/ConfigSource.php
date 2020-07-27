<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The cloud-docker.yml config
 */
class ConfigSource implements SourceInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     */
    public function __construct(DirectoryList $directoryList, Filesystem $filesystem)
    {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $configFile = $this->directoryList->getMagentoRoot() . '/.magento.docker.yml';

        if (!$this->filesystem->exists($configFile)) {
            $configFile = $this->directoryList->getMagentoRoot() . '/.magento.docker.yaml';
        }

        $repository = new Repository();

        try {
            if ($this->filesystem->exists($configFile)) {
                $config = Yaml::parseFile($configFile);

                if (!isset($config['name'])) {
                    throw new SourceException('Name could not be parsed.');
                }

                /**
                 * Enable services which were added from the file by default
                 */
                if (!empty($config[self::SERVICES])) {
                    foreach (array_keys($config[self::SERVICES]) as $service) {
                        $config[self::SERVICES][$service]['enabled'] = $config[self::SERVICES][$service]['enabled']
                            ?? true;
                    }
                }

                $repository->set($config);
            }
        } catch (ParseException $exception) {
            throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $repository;
    }
}
