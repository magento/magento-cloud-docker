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
    protected array $config = [
        'system_magento_dir' => '',
        'printOutput' => false,
    ];

    /**
     * @var array
     */
    protected array $services = [];

    /**
     * @inheritdoc
     */
    public function _initialize(): void
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
        // Validate inputs
        if (empty($source)) {
            throw new \RuntimeException("Source path is empty.");
        }
        if (empty($destination)) {
            throw new \RuntimeException("Destination path is empty.");
        }
        if (empty($container)) {
            throw new \RuntimeException("Container name is empty.");
        }
    
        // Log inputs
        error_log("Starting downloadFromContainer...");
        error_log("Source: $source");
        error_log("Destination: $destination");
        error_log("Container: $container");
    
        $printOutput = $this->_getConfig('printOutput');
        if (!is_bool($printOutput)) {
            throw new \RuntimeException("Invalid 'printOutput' configuration. Expected a boolean value.");
        }
    
        // Validate 'system_magento_dir' configuration
        $systemMagentoDir = $this->_getConfig('system_magento_dir');
        if (empty($systemMagentoDir)) {
            throw new \RuntimeException("Invalid 'system_magento_dir' configuration. It cannot be empty.");
        }
    
        // Log configuration
        error_log("System Magento Directory: $systemMagentoDir");
    
        // Validate source path
        $fullSourcePath = $systemMagentoDir . $source;
        if (!is_string($fullSourcePath) || empty($fullSourcePath)) {
            throw new \RuntimeException("Invalid source path. Constructed path: $fullSourcePath");
        }
    
        // Log full source path
        error_log("Full Source Path: $fullSourcePath");
    
        // Log task execution
        error_log("Executing taskCopyFromDocker...");
    
        /** @var Result $result */
        $result = $this->taskCopyFromDocker($container)
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->source($fullSourcePath)
            ->destination($destination)
            ->dir($this->getWorkDirPath())
            ->run();
    
        // Log result status
        if ($result->wasSuccessful()) {
            error_log("taskCopyFromDocker completed successfully.");
        } else {
            error_log("Inspecting Result object...");
            var_dump($result); // Inspect the full Result object
            $message = $result->getMessage();
            if (empty($message)) {
                $message = 'Unknown error occurred during task execution.';
            } elseif (!is_string($message)) {
                $message = json_encode($message);
            }
            error_log("taskCopyFromDocker failed. Message: $message");
            throw new \RuntimeException("Task failed: " . $message);
        }
    
        static::$output = $result->getMessage();
    
        // Log final output
        error_log("Task output: " . static::$output);
    
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
          // Check if the system has write permissions for the temporary directory
        $tempDir = sys_get_temp_dir();
        if (!is_writable($tempDir)) {
            throw new \RuntimeException("Temporary directory is not writable: $tempDir");
        }

        $tmpFile = tempnam(sys_get_temp_dir(), md5($source));
        if (!file_exists($tmpFile)) {
            throw new \RuntimeException("Temporary file is empty or not created: $tmpFile");
        }
        if (!is_writable($tmpFile)) {
            throw new \RuntimeException("Temporary file is not writable: $tmpFile");
        }
        if (!$this->downloadFromContainer($source, $tmpFile, $container)) {
            $errorMessage = sprintf(
                "Failed to download file from container. Source: %s, Container: %s, Temporary File: %s",$source, $container,$tmpFile
            );
            throw new \RuntimeException($errorMessage);
        }
        // static::$output = $this->downloadFromContainer($source, $tmpFile, $container);
        $content = file_get_contents($tmpFile);
        if ($content === false || $content === '') {
            throw new \RuntimeException("File is empty: $source");
        }
        return file_get_contents($tmpFile);
    }
}
