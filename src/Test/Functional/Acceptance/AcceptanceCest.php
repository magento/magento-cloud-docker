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
        $I->createArtifactCurrentTestedCode('docker', '1.1.99');
        $I->addArtifactsRepoToComposer();
        $I->addDependencyToComposer('magento/magento-cloud-docker', '1.1.99');
        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     */
    public function testProductionMode(\CliTester $I): void
    {
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
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
        $I->resetFilesOwner();
        $I->stopEnvironment();
        $I->removeDockerCompose();
        $I->removeWorkDir();
    }
}
