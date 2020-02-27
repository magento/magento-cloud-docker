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
 * Copy files from Docker environment
 */
class CopyFromDocker extends BaseTask implements CommandInterface
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
     * Sets the source path on the Docker
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
     * Sets the destination path on the local machine
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
            'docker cp "$(docker-compose ps -q %s)":%s %s',
            $this->container,
            $this->source,
            $this->destination
        );
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        if (!$this->destination) {
            throw new \RuntimeException('The destination path is empty');
        }

        if (!$this->source) {
            throw new \RuntimeException('The source path is empty');
        }

        $dir = pathinfo($this->destination, PATHINFO_DIRNAME);

        if (!is_dir($dir)) {
            mkdir(pathinfo($this->destination, PATHINFO_DIRNAME), 0755, true);
        }

        return $this->executeCommand($this->getCommand());
    }
}
