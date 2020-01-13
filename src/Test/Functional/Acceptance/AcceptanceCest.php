<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php73
 */
class AcceptanceCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.3.3';

    /**
     * @param \CliTester $I
     */
    public function _before(\CliTester $I): void
    {
        $I->cleanupWorkDir();
        $I->cloneTemplateToWorkDir(static::TEMPLATE_VERSION);
        $I->createAuthJson();
        $I->createArtifactsDir();
        $I->createEceDockerArtifact();
        $I->addArtifactsRepoToComposer();
        $I->addArtifactEceDockerToComposer();
        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     */
    public function testProductionMode(\CliTester $I): void
    {
        $I->runEceDockerCommand('build:compose --mode=production --no-cron');
        $I->startEnvironment();
        $I->runBashCommand('ls -al');
        $I->runBashCommand('ls -al ..');
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->runBashCommand('docker-compose logs db');
        $I->runBashCommand('docker ps');
        $I->runBashCommand('docker-compose ps');
        $I->runBashCommand('ls -al');
        $I->runBashCommand('ls -al ..');
        $I->stopEnvironment();
        $I->runBashCommand('ls -al');
        $I->runBashCommand('ls -al ..');
        $I->removeDockerCompose();
        $I->removeWorkDir();
    }
}
