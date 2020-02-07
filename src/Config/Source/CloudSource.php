<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Read and combine infrastructure configuration
 */
class CloudSource implements SourceInterface
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
     * @var ServiceFactory
     */
    private $serviceFactory;

    /**
     * @var array
     */
    private static $map = [
        ServiceInterface::SERVICE_DB => ['db', 'database', 'mysql'],
        ServiceInterface::SERVICE_ELASTICSEARCH => ['elasticsearch', 'es'],
        ServiceInterface::SERVICE_REDIS => ['redis']
    ];

    /**
     * @param FileList $fileList
     * @param Filesystem $filesystem
     * @param ServiceFactory $serviceFactory
     */
    public function __construct(FileList $fileList, Filesystem $filesystem, ServiceFactory $serviceFactory)
    {
        $this->fileList = $fileList;
        $this->filesystem = $filesystem;
        $this->serviceFactory = $serviceFactory;
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
            throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        if (!isset($appConfig['type'])) {
            throw new SourceException('PHP version could not be parsed.');
        }

        if (!isset($appConfig['relationships'])) {
            throw new SourceException('Relationships could not be parsed.');
        }

        [$type, $version] = explode(':', $appConfig['type']);

        if ($type !== ServiceInterface::SERVICE_PHP) {
            throw new SourceException(sprintf(
                'Type "%s" is not supported',
                $type
            ));
        }

        $repository = new Repository();
        $repository->set([
            self::PHP_ENABLED => true,
            self::PHP_VERSION => rtrim($version, '-rc')
        ]);

        if (!empty($appConfig['crons'])) {
            $repository->set([
                self::CRON_ENABLED => true,
                self::CRON_JOBS => $appConfig['crons']
            ]);
        }

        if (!empty($appConfig['mounts'])) {
            $repository[self::MOUNTS] = $appConfig['mounts'];
        }

        if (!empty($appConfig['runtime']['extensions'])) {
            $repository[self::PHP_EXTENSIONS] = $appConfig['runtime']['extensions'];
        }

        if (!empty($appConfig['runtime']['disabled_extensions'])) {
            $repository[self::PHP_DISABLED_EXTENSIONS] = $appConfig['runtime']['disabled_extensions'];
        }

        foreach ($appConfig['relationships'] as $constraint) {
            [$name] = explode(':', $constraint);

            if (!isset($servicesConfig[$name]['type'])) {
                throw new SourceException(sprintf(
                    'Service with name "%s" could not be parsed',
                    $name
                ));
            }

            [$parsedService, $version] = explode(':', $servicesConfig[$name]['type']);

            foreach (self::$map as $service => $possibleNames) {
                if (in_array($parsedService, $possibleNames, true)) {
                    if ($repository->has('services.' . $service)) {
                        throw new SourceException(sprintf(
                            'Only one instance of service "%s" supported',
                            $service
                        ));
                    }

                    try {
                        $repository->set([
                            self::SERVICES . '.' . $service . '.version' => $version,
                            self::SERVICES . '.' . $service . '.image' => $this->serviceFactory->getImage($service),
                            self::SERVICES . '.' . $service . '.enabled' => true
                        ]);
                    } catch (ConfigurationMismatchException $exception) {
                        throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
                    }
                }
            }
        }

        return $repository;
    }
}
