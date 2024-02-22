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
use Magento\CloudDocker\Test\Functional\Codeception\Docker;

/**
 * Clean Up Docker environment
 */
class EnvCleanUp extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @var array
     */
    private $volumes;

    /**
     * @param array $volumes
     */
    public function __construct(array $volumes)
    {
        $this->volumes = $volumes;
    }

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        $commands = [
            'docker-compose down -v --remove-orphans',
        ];

        foreach ($this->volumes as $volume) {
            $commands[] = sprintf(
                'docker-compose run %s bash -c "mkdir -p %s"',
                Docker::BUILD_CONTAINER,
                $volume
            );
        }

        $commands[] = 'docker-compose up -d build';

        return implode(' && ', $commands);
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
