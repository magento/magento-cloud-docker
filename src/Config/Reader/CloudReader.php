<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Reader;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\ServiceInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Read and combine infrastructure configuration.
 */
class CloudReader implements ReaderInterface
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
    public function read(): Repository
    {
        $appConfigFile = $this->fileList->getAppConfig();
        $servicesConfigFile = $this->fileList->getServicesConfig();

        if (!$this->filesystem->exists($appConfigFile) || !$this->filesystem->exists($servicesConfigFile)) {
            return new Repository();
        }

        try {
            $appConfig = Yaml::parse(
                $this->filesystem->get($this->fileList->getAppConfig())
            );
            $servicesConfig = Yaml::parse(
                $this->filesystem->get($this->fileList->getServicesConfig())
            );
        } catch (\Exception $exception) {
            throw new ReaderException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!isset($appConfig['type'])) {
            throw new ReaderException('PHP version could not be parsed.');
        }

        if (!isset($appConfig['relationships'])) {
            throw new ReaderException('Relationships could not be parsed.');
        }

        $config = [
            self::SERVICES => [],
        ];

        [$type, $version] = explode(':', $appConfig['type']);

        if ($type !== ServiceInterface::NAME_PHP) {
            throw new ReaderException(sprintf(
                'Type "%s" is not supported',
                $type
            ));
        }


        $config[self::PHP] = rtrim($version, '-rc');

        if (!empty($appConfig['crons'])) {
            $config[self::CRONS] = $appConfig['crons'];
        }

        if (!empty($appConfig['mounts'])) {
            $config[self::MOUNTS] = $appConfig['mounts'];
        }

        if (!empty($appConfig['runtime']['extensions'])) {
            $config[self::RUNTIME_EXTENSIONS] = $appConfig['runtime']['extensions'];
        }

        if (!empty($appConfig['runtime']['disabled_extensions'])) {
            $config[self::RUNTIME_DISABLED_EXTENSIONS] = $appConfig['runtime']['disabled_extensions'];
        }

        foreach ($appConfig['relationships'] as $constraint) {
            [$name] = explode(':', $constraint);

            if (!isset($servicesConfig[$name]['type'])) {
                throw new ReaderException(sprintf(
                    'Service with name "%s" could not be parsed',
                    $name
                ));
            }

            [$service, $version] = explode(':', $servicesConfig[$name]['type']);

            if (array_key_exists($service, $config['services'])) {
                throw new ReaderException(sprintf(
                    'Only one instance of service "%s" supported',
                    $service
                ));
            }

            $config[self::SERVICES][$service] = [
                'service' => $service,
                'version' => $version
            ];
        }

        return new Repository($config);
    }
}
