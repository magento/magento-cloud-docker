<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Codeception;

use Codeception\Module;
use Magento\CloudDocker\Test\Functional\Robo\Tasks as CloudDockerTasks;
use Robo\LoadAllTasks as RoboTasks;
use Robo\Robo;
use Robo\Collection\CollectionBuilder;
use Robo\Contract\BuilderAwareInterface;
use League\Container\ContainerAwareInterface;
use League\Container\ContainerAwareTrait;
use PHPUnit\Framework\Assert;

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
     * Cached work directory name
     */
    const WORK_DIR_CACHE = '_workdir_cache';

    /**
     * The artifact directory name
     */
    const ARTIFACTS_DIR = 'artifacts';

    /**
     * The file with app configuration
     */
    const MAGENTO_APP_YAML = '.magento.app.yaml';

    /**
     * The file with env configuration
     */
    const MAGENTO_ENV_YAML = '.magento.env.yaml';

    /**
     * The file with defined services
     */
    const MAGENTO_SERVICES_YAML = '.magento' . DIRECTORY_SEPARATOR . 'services.yaml';

    /**
     * @var string
     */
    protected static $output = '';

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
     * Updates Base Url for PhpBrowser module
     *
     * @param string $url
     * @throws \Codeception\Exception\ModuleConfigException
     * @throws \Codeception\Exception\ModuleException
     */
    public function updateBaseUrl(string $url)
    {
        $this->getModule('PhpBrowser')->_reconfigure(['url' => $url]);
    }

    /**
     * Returns the path to root directory
     *
     * @return string
     */
    public function getRootDirPath(): string
    {
        return codecept_root_dir();
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
     * Returns the path to cached work directory
     *
     * @param string $version
     * @return string
     */
    public function getCachedWorkDirPath(string $version): string
    {
        return codecept_root_dir(self::WORK_DIR_CACHE) . '/' . $version;
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

    /**
     * Checks that output contains $text
     *
     * @param string|string[] $text
     */
    public function seeInOutput($text): void
    {
        if (is_array($text)) {
            foreach ($text as $value) {
                Assert::assertContains($value, static::$output);
            }
        } else {
            Assert::assertContains($text, static::$output);
        }
    }

    /**
     * Checks that output does not contain $text
     *
     * @param string|string[] $text
     */
    public function doNotSeeInOutput($text): void
    {
        if (is_array($text)) {
            foreach ($text as $value) {
                Assert::assertNotContains($value, static::$output);
            }
        } else {
            Assert::assertNotContains($text, static::$output);
        }
    }

    /**
     * Returns output of last run command
     *
     * @return string
     */
    public function getOutput(): string
    {
        return static::$output;
    }
}
