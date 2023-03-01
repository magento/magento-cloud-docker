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

        if ($I->isCacheWorkDirExists(static::TEMPLATE_VERSION)) {
            $I->restoreWorkDirFromCache(static::TEMPLATE_VERSION);

            return;
        }

        $I->cloneTemplateToWorkDir(static::TEMPLATE_VERSION);
        $I->createAuthJson();
        $I->createArtifactsDir();
        $I->createArtifactCurrentTestedCode('docker', '1.3.5');
        $I->addArtifactsRepoToComposer();
        $I->addDependencyToComposer('magento/magento-cloud-docker', '1.3.5');

        $I->addEceToolsGitRepoToComposer();
        $I->addDependencyToComposer(
            'magento/ece-tools',
            $I->getDependencyVersion('magento/ece-tools') ?: 'dev-develop as 2002.1.99'
        );

        if ($mccVersion = $I->getDependencyVersion('magento/magento-cloud-components')) {
            $I->addCloudComponentsGitRepoToComposer();
            $I->addDependencyToComposer('magento/magento-cloud-components', $mccVersion);
        }

        if ($mcpVersion = $I->getDependencyVersion('magento/magento-cloud-patches')) {
            $I->addCloudPatchesGitRepoToComposer();
            $I->addDependencyToComposer('magento/magento-cloud-patches', $mcpVersion);
        }

        if ($mqpVersion = $I->getDependencyVersion('magento/quality-patches')) {
            $I->addQualityPatchesGitRepoToComposer();
            $I->addDependencyToComposer('magento/quality-patches', $mqpVersion);
        }

        $I->assertTrue($I->composerUpdate(), 'Composer update failed');
        $I->cacheWorkDir(static::TEMPLATE_VERSION);
    }

    /**
     * @param \CliTester $I
     */
    public function _after(\CliTester $I): void
    {
        $I->runDockerComposeCommand('ps');
        $I->stopEnvironment();
        $I->removeWorkDir();
    }
}
