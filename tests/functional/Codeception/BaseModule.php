<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Codeception;

use Codeception\Module;
use Magento\CloudDocker\Test\Functional\Robo\Tasks as CloudDockerTasks;
use PHPUnit\Framework\Assert;
use Robo\LoadAllTasks as RoboTasks;
use Robo\Robo;
use Robo\Result;
use Codeception\Configuration;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use Robo\Exception\TaskException;

/**
 * Base Module for testing
 */
class BaseModule extends Module implements BuilderAwareInterface, ContainerAwareInterface
{
    use ContainerAwareTrait;

    use RoboTasks, CloudDockerTasks {
        RoboTasks::getBuilder insteadof CloudDockerTasks;
        RoboTasks::setBuilder insteadof CloudDockerTasks;
        RoboTasks::collectionBuilder insteadof CloudDockerTasks;
        RoboTasks::getBuiltTask insteadof CloudDockerTasks;
        RoboTasks::task insteadof CloudDockerTasks;
    }

    /**
     * The work directory name
     */
    const WORK_DIR = '_workdir';

    /**
     * The artifact directory name
     */
    const ARTIFACTS_DIR = 'artifacts';

    /**
     * @inheritdoc
     */
    public function _initialize()
    {
        $container = Robo::createDefaultContainer();
        $builder = CollectionBuilder::create($container, $this);

        $this->setContainer($container);
        $this->setBuilder($builder);
    }

    /**
     * Returns the path to work directory
     *
     * @return string
     */
    public function getWorkDirPath(): string
    {
        return codecept_root_dir(self::WORK_DIR);
    }

    /**
     * Returns the path to directory that contains artifacts
     *
     * @return string
     */
    public function getArtifactsDir(): string
    {
        return codecept_root_dir(self::WORK_DIR . DIRECTORY_SEPARATOR . self::ARTIFACTS_DIR);
    }
}
