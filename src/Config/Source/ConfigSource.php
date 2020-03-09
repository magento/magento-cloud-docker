<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Yaml\Exception\ParseException;
use Symfony\Component\Yaml\Yaml;

/**
 * The cloud-docker.yml config
 */
class ConfigSource implements SourceInterface
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param FileList $fileList
     * @param Filesystem $filesystem
     */
    public function __construct(FileList $fileList, Filesystem $filesystem)
    {
        $this->fileList = $fileList;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $configFile = $this->fileList->getDockerConfig();
        $repository = new Repository();

        try {
            if ($this->filesystem->exists($configFile)) {
                $config = Yaml::parseFile($configFile);

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
