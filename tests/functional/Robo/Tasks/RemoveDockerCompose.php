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
 * Remove docker-compose.yml
 */
class RemoveDockerCompose extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return 'rm docker-compose.yml';
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        return $this->executeCommand($this->getCommand());
    }
}
