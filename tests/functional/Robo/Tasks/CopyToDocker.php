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
        return sprintf(
            'docker cp %s "$(docker-compose ps -q %s)":%s',
            $this->source,
            $this->container,
            $this->destination
        );
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
