<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Robo;

use Robo\Collection\CollectionBuilder;
use Robo\TaskAccessor;

/**
 * Tasks loader.
 */
trait Tasks
{
    use TaskAccessor;

    /**
     * @param array $volumes
     * @return Tasks\EnvCleanUp|CollectionBuilder
     */
    protected function taskEnvCleanUp(array $volumes): CollectionBuilder
    {
        return $this->task(Tasks\EnvCleanUp::class, $volumes);
    }

    /**
     * @param array $volumes
     * @return Tasks\EnvUp|CollectionBuilder
     */
    protected function taskEnvUp(): CollectionBuilder
    {
        return $this->task(Tasks\EnvUp::class);
    }

    /**
     * @return Tasks\EnvDown|CollectionBuilder
     */
    protected function taskEnvDown(): CollectionBuilder
    {
        return $this->task(Tasks\EnvDown::class);
    }

    /**
     * @param string $container
     * @return Tasks\Bash|CollectionBuilder
     */
    protected function taskBash(string $container): CollectionBuilder
    {
        return $this->task(Tasks\Bash::class, $container);
    }

    /**
     * @param string $container
     * @return Tasks\DockerCompose\Run|CollectionBuilder
     */
    protected function taskDockerComposeRun(string $container): CollectionBuilder
    {
        return $this->task(Tasks\DockerCompose\Run::class, $container);
    }

    /**
     * @param string $container
     * @return Tasks\CopyFromDocker|CollectionBuilder
     */
    protected function taskCopyFromDocker(string $container): CollectionBuilder
    {
        return $this->task(Tasks\CopyFromDocker::class, $container);
    }

    /**
     * @param string $container
     * @return Tasks\CopyToDocker|CollectionBuilder
     */
    protected function taskCopyToDocker(string $container): CollectionBuilder
    {
        return $this->task(Tasks\CopyToDocker::class, $container);
    }

    /**
     * @param array $services
     * @return Tasks\GenerateDockerCompose|CollectionBuilder
     */
    protected function taskGenerateDockerCompose(array $services = []): CollectionBuilder
    {
        return $this->task(Tasks\GenerateDockerCompose::class, $services);
    }

    /**
     * @return Tasks\RemoveDockerCompose|CollectionBuilder
     */
    protected function taskRemoveDockerCompose(): CollectionBuilder
    {
        return $this->task(Tasks\RemoveDockerCompose::class);
    }

    /**
     * @param string $command
     * @return Tasks\DockerCompose|CollectionBuilder
     */
    protected function taskDockerCompose(string $command): CollectionBuilder
    {
        return $this->task(Tasks\DockerCompose::class, $command);
    }
}
