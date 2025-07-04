<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Robo\Exception\TaskException;

/**
 * @group php83
 */
class ValkeyCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @return void
     * @throws TaskException
     */
    public function testValkey(CliTester $I, Example $data)
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        
        // Test that Valkey is running
        $I->runDockerComposeCommand('exec -T valkey ps aux | grep valkey');
        $I->seeInOutput('valkey-server');
        
        // Test Valkey connectivity using valkey-cli
        $I->runDockerComposeCommand('exec -T valkey valkey-cli ping');
        $I->seeInOutput('PONG');
        
        // Test that Valkey is accessible through cache alias
        $I->runDockerComposeCommand('exec -T fpm valkey-cli -h cache ping');
        $I->seeInOutput('PONG');
        
        // Test that Valkey is accessible through valkey.magento2.docker alias
        $I->runDockerComposeCommand('exec -T fpm valkey-cli -h valkey.magento2.docker ping');
        $I->seeInOutput('PONG');
        
        // Test basic Valkey functionality
        $I->runDockerComposeCommand('exec -T valkey valkey-cli set test_key "test_value"');
        $I->seeInOutput('OK');
        
        $I->runDockerComposeCommand('exec -T valkey valkey-cli get test_key');
        $I->seeInOutput('test_value');
        
        // Test Valkey info command to verify version
        $I->runDockerComposeCommand('exec -T valkey valkey-cli info server');
        $I->seeInOutput('valkey_version:' . $data['version']);
        
        // Test that Valkey is running on the expected port
        $I->runDockerComposeCommand('exec -T valkey netstat -tlnp | grep 6379');
        $I->seeInOutput('6379');
        
        // Test memory usage reporting
        $I->runDockerComposeCommand('exec -T valkey valkey-cli info memory');
        $I->seeInOutput('used_memory:');
        $I->seeInOutput('used_memory_human:');
        
        // Test health check functionality
        $I->runDockerComposeCommand('exec -T valkey valkey-cli ping');
        $I->seeInOutput('PONG');
        
        // Test data persistence
        $I->runDockerComposeCommand('exec -T valkey valkey-cli set persistent_key "persistent_value"');
        $I->seeInOutput('OK');
        
        $I->runDockerComposeCommand('exec -T valkey valkey-cli get persistent_key');
        $I->seeInOutput('persistent_value');
        
        // Test database operations
        $I->runDockerComposeCommand('exec -T valkey valkey-cli dbsize');
        $I->seeInOutput('2'); // We should have 2 keys: test_key and persistent_key
        
        // Test that Valkey configuration is accessible
        $I->runDockerComposeCommand('exec -T valkey valkey-cli config get save');
        $I->seeInOutput('save');
    }

    /**
     * Builds build:compose command from given test data
     *
     * @param Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
        $command = sprintf(
            '--mode=production --valkey=%s --no-es --no-os',
            $data['version']
        );

        return $command;
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '8.0',
            ],
        ];
    }
} 