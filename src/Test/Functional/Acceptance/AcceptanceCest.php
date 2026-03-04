<?php
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Codeception\Exception\ModuleConfigException;
use Codeception\Exception\ModuleException;
use Robo\Exception\TaskException;

/**
 * General acceptance tests for Magento Cloud Docker.
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * Default production mode test
     *
     * @param CliTester $I
     * @return void
     * @throws TaskException
     * @throws ModuleConfigException
     * @throws ModuleException
     */
    public function testProductionMode(CliTester $I): void
    {
        $I->assertTrue($I->generateDockerCompose('--mode=production'), 'Command build:compose failed');
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase failed');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Custom host production mode test
     *
     * @param CliTester $I
     * @return void
     * @throws TaskException
     * @throws ModuleConfigException
     * @throws ModuleException
     */
    public function testCustomHost(CliTester $I): void
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
