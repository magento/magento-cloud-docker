<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * General Cest
 */
abstract class AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = 'master';

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

        $I->addEceToolsGitRepoToComposer();
        $I->addDependencyToComposer('magento/ece-tools', 'dev-develop as 2002.1.99');

        $I->composerUpdate();
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->stopEnvironment();
        $I->removeWorkDir();
    }
}
