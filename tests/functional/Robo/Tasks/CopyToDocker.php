<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Robo\Tasks;

use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Copy files to Docker environment
 */
class CopyToDocker extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * Container name
     *
     * @var string
     */
    protected $container;

    /**
     * Path to file on the Docker environment
     *
     * @var string
     */
    protected $source;

    /**
     * Path to file on the local machine
     *
     * @var string
     */
    protected $destination;

    /**
     * @param string $container
     */
    public function __construct(string $container)
    {
        $this->container = $container;
    }

    /**
     * Sets the source path on the local machine
     *
     * @param string $source
     * @return self
     */
    public function source(string $source): self
    {
        $this->source = $source;

        return $this;
    }

    /**
     * Sets the destination path on the Docker
     *
     * @param string $destination
     * @return self
     */
    public function destination(string $destination): self
    {
        $this->destination = $destination;

        return $this;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
{
    error_log('Container for cp: ' . $this->container);
    error_log('Source: ' . $this->source);
    error_log('Destination: ' . $this->destination);
 
    if (!$this->container) {
        throw new \RuntimeException('No container defined for copy operation.');
    }
 
    $rawOutput = shell_exec(sprintf('docker-compose ps -q %s', escapeshellarg($this->container)));
    if ($rawOutput === null) {
        throw new \RuntimeException(sprintf(
            'Failed to execute docker-compose command for container "%s".',
            $this->container
        ));
    }
 
    $containerId = trim($rawOutput);
    if ($containerId === '') {
        throw new \RuntimeException(sprintf(
            'Container "%s" is not running or does not exist.',
            $this->container
        ));
    }
 
    error_log('Resolved container ID: ' . $containerId);
 
    // Safely escape arguments
    $command = sprintf(
        'docker cp %s:%s %s',
        escapeshellarg($containerId),
        escapeshellarg($this->source),
        escapeshellarg($this->destination)
    );
 
    error_log('Generated command: ' . $command);
 
    return $command;
}

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        if (!file_exists($this->source)) {
            throw new \RuntimeException(sprintf('The path "%s" does not exist', $this->source));
        }

        if (!$this->destination) {
            throw new \RuntimeException('The destination path is empty');
        }

        return $this->executeCommand($this->getCommand());
    }
}