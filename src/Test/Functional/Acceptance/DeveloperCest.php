<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;

/**
 * @group php83
 */
class DeveloperCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * Tests that php settings contains configuration from php.dev.ini
     *
     * @param CliTester $I
     * @throws \Robo\Exception\TaskException
     */
    public function testDevPhpIni(CliTester $I)
    {
        $I->generateDockerCompose('--mode=developer');
        $I->replaceImagesWithCustom();
        $I->startEnvironment();

        $I->runDockerComposeCommand('run deploy php -i | grep opcache.validate_timestamps');
        $I->seeInOutput('=> On');

        $I->runDockerComposeCommand('run fpm php -i | grep opcache.validate_timestamps');
        $I->seeInOutput('=> On');
    }
}
