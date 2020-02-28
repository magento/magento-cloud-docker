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
 * Generate docker-compose.yml
 */
class GenerateDockerCompose extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @var array
     */
    private $services;

    /**
     * @param array $services
     * @throws \RuntimeException
     */
    public function __construct(array $services = [])
    {
        if (!isset($services['php'])) {
            $services['php'] = PHP_MAJOR_VERSION . '.' . PHP_MINOR_VERSION;
        }

        $this->services = $services;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        $command = './vendor/bin/ece-docker build:compose --mode=functional';

        foreach ($this->services as $service => $version) {
            $command .= sprintf(' --%s=%s', $service, $version);
        }

        return $command;
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
