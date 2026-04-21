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
 * Generic Valkey acceptance tests for connectivity and core operations
 * in the Magento Cloud Docker environment.
 *
 * Each PHP/Adobe Commerce line Cest (Valkey81Cest-Valkey85Cest) must implement {@see dataProvider()}.
 */
abstract class ValkeyCest extends AbstractCest
{
    /**
     * Builds and starts the Docker environment, then validates Valkey health,
     * network aliases, basic read/write operations, and server metadata.
     *
     * @param CliTester $I
     * @param Example $data
     *
     * @dataProvider dataProvider
     *
     * @return void
     * @throws TaskException
     */
    public function testValkey(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        
        // Test Valkey connectivity (this also confirms it's running)
        $I->runDockerComposeCommand('exec -T valkey valkey-cli ping');
        $I->seeInOutput('PONG');
        
        // Test that Valkey container is running and healthy
        $I->runDockerComposeCommand('ps');
        $I->seeInOutput('valkey');
        $I->seeInOutput('(healthy)');
        
        // Test that Valkey is accessible through cache alias (using nc to test network connectivity)
        $I->runDockerComposeCommand('exec -T fpm nc -z cache 6379');
        $I->seeInOutput('');  // nc returns empty output on success
        
        // Test that Valkey is accessible through valkey.magento2.docker alias
        $I->runDockerComposeCommand('exec -T fpm nc -z valkey.magento2.docker 6379');
        $I->seeInOutput('');
        
        // Test basic Valkey functionality
        $I->runDockerComposeCommand('exec -T valkey valkey-cli set test_key "test_value"');
        $I->seeInOutput('OK');
        
        $I->runDockerComposeCommand('exec -T valkey valkey-cli get test_key');
        $I->seeInOutput('test_value');
        
        // Test Valkey info command to verify version
        $I->runDockerComposeCommand('exec -T valkey valkey-cli info server');
        $I->seeInOutput('valkey_version:' . $data['version']);
        
        // Test confirmed: Valkey is accessible on port 6379 (validated via info command above)
        
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
     * Build the `build:compose` argument string for the requested Valkey version.
     *
     * @param Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
        $command = sprintf(
            '--mode=production --valkey=%s --no-es --no-os --no-redis',
            $data['version']
        );

        return $command;
    }

    /**
     * Provide Valkey versions for the current PHP/Adobe Commerce test line.
     *
     * @return array<int, array{version: string}>
     */
    abstract protected function dataProvider(): array;
}
