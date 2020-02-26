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
 * Down Docker environment
 */
class EnvDown extends BaseTask implements CommandInterface
{
    use ExecOneCommand;

    /**
     * @var bool
     */
    private $keepVolumes = false;

    /**
     * @inheritdoc
     */
    public function getCommand(): string
    {
        return 'docker-compose down --remove-orphans';
    }

    /**
     * @inheritdoc
     */
    public function run(): Result
    {
        $command = $this->getCommand();
        $command .= $this->keepVolumes ? '' : ' -v';

        return $this->executeCommand($command);
    }

    /**
     * @param bool $value
     * @return EnvDown
     */
    public function keepVolumes(bool $value = true): self
    {
        $this->keepVolumes = $value;

        return $this;
    }
}
