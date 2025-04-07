<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php83
 */
class MailHogCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * @param \CliTester $I
     * @throws \Exception
     */
    public function testDefaultPorts(\CliTester $I): void
    {
        $I->updateBaseUrl('http://magento2.docker:8025/');
        $I->assertTrue(
            $I->generateDockerCompose(),
            'Command build:compose failed'
        );
        $this->runAndAssert($I);
    }

    /**
     * @param \CliTester $I
     * @throws \Exception
     */
    public function testCustomPorts(\CliTester $I): void
    {
        $I->updateBaseUrl('http://magento2.docker:8026/');
        $I->assertTrue(
            $I->generateDockerCompose('--mailhog-http-port=8026 --mailhog-smtp-port=1026'),
            'Command build:compose failed'
        );
        $this->runAndAssert($I);
    }

    /**
     * @param \CliTester $I
     * @throws \Exception
     */
    private function runAndAssert(\CliTester $I): void
    {
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        $I->amOnPage('/');
        $I->see('MailHog');

        $I->sendAjaxGetRequest('/api/v2/messages', ['limit' => 10]);
        $I->seeResponseIsJson();
        $I->assertSame([0], $I->grabDataFromResponseByJsonPath('$.total'));

        $I->assertTrue(
            $I->runDockerComposeCommand('run deploy bash -c "php -r \"mail(\'test@example.com\',\'test\',\'test\');\""')
        );
        $I->sendAjaxGetRequest('/api/v2/messages', ['limit' => 10]);
        $I->seeResponseIsJson();
        $I->assertSame([1], $I->grabDataFromResponseByJsonPath('$.total'));
    }
}
