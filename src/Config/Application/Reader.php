<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Application;

use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Symfony\Component\Yaml\Yaml;
use Magento\CloudDocker\Config\ReaderInterface;

/**
 * Read and combine infrastructure configuration.
 */
class Reader implements ReaderInterface
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
     * @inheritdoc
     */
    public function read(): array
    {
        try {
            $appConfig = Yaml::parse(
                $this->filesystem->get($this->fileList->getAppConfig())
            );
            $servicesConfig = Yaml::parse(
                $this->filesystem->get($this->fileList->getServicesConfig())
            );
        } catch (FileNotFoundException $exception) {
            throw new FilesystemException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!isset($appConfig['type'])) {
            throw new FilesystemException('PHP version could not be parsed.');
        }

        if (!isset($appConfig['relationships'])) {
            throw new FilesystemException('Relationships could not be parsed.');
        }

        $config = [
            'type' => $appConfig['type'],
            'crons' => $appConfig['crons'] ?? [],
            'services' => [],
            'runtime' => [
                'extensions' => $appConfig['runtime']['extensions'] ?? [],
                'disabled_extensions' => $appConfig['runtime']['disabled_extensions'] ?? []
            ],
            'mounts' => $appConfig['mounts'] ?? []
        ];

        foreach ($appConfig['relationships'] as $constraint) {
            [$name] = explode(':', $constraint);

            if (!isset($servicesConfig[$name]['type'])) {
                throw new FilesystemException(sprintf(
                    'Service with name "%s" could not be parsed',
                    $name
                ));
            }

            [$service, $version] = explode(':', $servicesConfig[$name]['type']);

            if (array_key_exists($service, $config['services'])) {
                throw new FilesystemException(sprintf(
                    'Only one instance of service "%s" supported',
                    $service
                ));
            }

            $config['services'][$service] = [
                'service' => $service,
                'version' => $version
            ];
        }

        return $config;
    }
}
