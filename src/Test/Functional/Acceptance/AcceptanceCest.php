<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use Robo\Exception\TaskException;

/**
 * @group php83
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * @param \CliTester $I
     * @throws TaskException
     */
    public function testProductionMode(\CliTester $I): void
    {
        $I->assertTrue($I->generateDockerCompose('--mode=production'), 'Command build:compose failed');
        $I->replaceImagesWithCustom();
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
        $I->updateBaseUrl('http://magento2.test/');
        $I->assertTrue(
            $I->generateDockerCompose('--mode=production --host=magento2.test'),
            'Command build:compose failed'
        );
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase failed');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
