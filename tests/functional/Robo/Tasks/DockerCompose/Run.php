<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Robo\Tasks\DockerCompose;

use Robo\Common\CommandReceiver;
use Robo\Common\ExecOneCommand;
use Robo\Contract\CommandInterface;
use Robo\Result;
use Robo\Task\BaseTask;

/**
 * Run docker-compose command
 */
class Run extends BaseTask implements CommandInterface
{
    use ExecOneCommand;
    use CommandReceiver;

    /**
     * Container name
     *
     * @var string
     */
    protected $container;

    /**
     * Command to run
     *
     * @var string
     */
    protected $run;

    /**
     * Command wrapper
     *
     * @var string
     */
    protected $runWrapper = '%s';

    /**
     * Docker working directory
     *
     * @var string
     */
    protected $workingDir = '/app';

    /**
     * @param string $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @inheritdoc
     */
    public function getCommand()
    {
        return trim(sprintf(
            'docker-compose run -w %s %s %s ' . $this->runWrapper,
            escapeshellarg($this->workingDir),
            $this->arguments,
            escapeshellarg($this->container),
            $this->run
        ));
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }

    /**
     * Sets the command to run
     *
     * @param string|\Robo\Contract\CommandInterface $run
     * @return $this
     * @throws \Robo\Exception\TaskException
     */
    public function exec($run)
    {
        $this->run = $this->receiveCommand($run);
        return $this;
    }

    /**
     * Set the command working directory.
     *
     * @param string
     * @return $this
     */
    public function workingDir(string $dir): self
    {
        $this->workingDir = $dir;
        return $this;
    }

    /**
     * Sets environment variables
     *
     * @param array $env
     * @return $this
     */
    public function envVars(array $env)
    {
        foreach ($env as $variable => $value) {
            $this->setDockerEnv($variable, $value);
        }
        return $this;
    }

    /**
     * @param string $variable
     * @param null|string $value
     *
     * @return $this
     */
    protected function setDockerEnv($variable, $value = null)
    {
        $env = $value ? sprintf('%s=%s', $variable, $value) : $variable;
        return $this->option('-e', $env);
    }
}
