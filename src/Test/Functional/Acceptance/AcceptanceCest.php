<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use Robo\Exception\TaskException;

/**
 * @group php74
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * @param \CliTester $I
     * @throws TaskException
     */
    public function testProductionMode(\CliTester $I): void
    {
        $I->assertTrue($I->runEceDockerCommand('build:compose --mode=production'), 'Command build:compose failed');
        $I->replaceImagesWithGenerated();
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * @param \CliTester $I
     * @throws TaskException
     * @throws \Codeception\Exception\ModuleConfigException
     * @throws \Codeception\Exception\ModuleException
     */
    public function testCustomHost(\CliTester $I): void
    {
        $I->updateBaseUrl('http://magento2.test:8080/');
        $I->assertTrue(
            $I->runEceDockerCommand('build:compose --mode=production --host=magento2.test --port=8080'),
            'Command build:compose failed'
        );
        $I->replaceImagesWithGenerated();
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase failed');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
