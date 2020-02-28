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
 * Run docker-compose commands
 */
class DockerCompose extends BaseTask implements CommandInterface
{
    use ExecOneCommand;
    /**
     * @var string
     */
    private $command;

    /**
     * @param string $command
     */
    public function __construct(string $command)
    {
        $this->command = $command;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return sprintf('docker-compose %s', $this->command);
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
