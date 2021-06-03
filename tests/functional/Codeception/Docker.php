<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Codeception;

use Robo\Robo;
use Robo\Result;
use Robo\Collection\CollectionBuilder;
use Robo\Exception\TaskException;

/**
 * Module for running commands on Docker environment
 */
class Docker extends BaseModule
{
    const BUILD_CONTAINER = 'build';
    const DEPLOY_CONTAINER = 'deploy';

    /**
     * @var array
     */
    protected $config = [
        'system_magento_dir' => '',
        'printOutput' => false,
    ];

    /**
     * @var array
     */
    protected $services = [];

    /**
     * @inheritdoc
     */
    public function _initialize()
    {
        $container = Robo::createDefaultContainer();
        $builder = CollectionBuilder::create($container, $this);

        $this->setContainer($container);
        $this->setBuilder($builder);
    }

    /**
     * Stops Docker env
     *
     * @param $keepVolumes bool
     * @return bool
     */
    public function stopEnvironment(bool $keepVolumes = false): bool
    {
        $this->resetFilesOwner();

        return $this->taskEnvDown()
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->keepVolumes($keepVolumes)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Start Docker env
     *
     * @return bool
     */
    public function startEnvironment(): bool
    {
        return $this->taskEnvUp()
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Run some docker-compose command
     *
     * @param string $command
     * @return bool
     */
    public function runDockerComposeCommand(string $command): bool
    {
        $result = $this->taskDockerCompose($command)
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Resets file owner
     *
     * @return bool
     */
    public function resetFilesOwner(): bool
    {
        return $this->runDockerComposeCommand(
            'run build bash -c "chown -R $(id -u):$(id -g) . /composer/cache"'
        );
    }

    /**
     * Removes docker-compose.yml
     *
     * @return bool
     */
    public function removeDockerCompose(): bool
    {
        return $this->taskRemoveDockerCompose()
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Cleans directories
     *
     * @param string|array $path
     * @param string $container
     * @return bool
     *
     * @throws TaskException
     */
    public function cleanDirectories($path, string $container = self::BUILD_CONTAINER): bool
    {
        $magentoRoot = $this->_getConfig('system_magento_dir');

        if (is_array($path)) {
            $path = array_map(
                static function ($val) use ($magentoRoot) {
                    return $magentoRoot . $val;
                },
                $path
            );
            $pathsToCleanup = implode(' ', $path);
        } else {
            $pathsToCleanup = $magentoRoot . $path;
        }

        /** @var Result $result */
        $result = $this->taskBash($container)
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec('rm -rf ' . $pathsToCleanup)
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Downloads files from Docker container
     *
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return bool
     */
    public function downloadFromContainer(string $source, string $destination, string $container): bool
    {
        /** @var Result $result */
        $result = $this->taskCopyFromDocker($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->source($this->_getConfig('system_magento_dir') . $source)
            ->destination($destination)
            ->dir($this->getWorkDirPath())
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Creates folder on Docker
     *
     * @param string $path
     * @param string $container
     * @return bool
     * @throws TaskException
     */
    public function createDirectory(string $path, string $container): bool
    {
        /** @var Result $result */
        $result = $this->taskBash($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->exec(sprintf('mkdir -p %s', $this->_getConfig('system_magento_dir') . $path))
            ->dir($this->getWorkDirPath())
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Uploads files to Docker container
     *
     * Relative paths for $source will be expanded from Codeception's data directory.
     *
     * @param string $source
     * @param string $destination
     * @param string $container
     * @return bool
     */
    public function uploadToContainer(string $source, string $destination, string $container): bool
    {
        if (strpos($source, '/') !== 0) {
            $source = codecept_data_dir($source);
        }

        /** @var Result $result */
        $result = $this->taskCopyToDocker($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->source($source)
            ->destination($this->_getConfig('system_magento_dir') . $destination)
            ->dir($this->getWorkDirPath())
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
    }

    /**
     * Returns file contents
     *
     * @param string $source
     * @param string $container
     * @return string|false
     */
    public function grabFileContent(string $source, string $container = self::DEPLOY_CONTAINER)
    {
        $tmpFile = tempnam(sys_get_temp_dir(), md5($source));
        $this->downloadFromContainer($source, $tmpFile, $container);

        return file_get_contents($tmpFile);
    }
}
