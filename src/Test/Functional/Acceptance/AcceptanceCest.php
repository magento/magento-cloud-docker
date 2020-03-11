<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use Robo\Exception\TaskException;

/**
 * @group php73
 */
class AcceptanceCest extends AbstractAcceptanceCest
{
    /**
     * @param \CliTester $I
     * @throws TaskException
     */
    public function testProductionMode(\CliTester $I): void
    {
        $I->runEceDockerCommand('build:compose --mode=production');
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
