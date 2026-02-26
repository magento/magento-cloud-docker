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
 * Generic ActiveMQ Artemis tests to validate configuration and
 * functionality within the Magento Cloud Docker environment.
 */
class ActivemqArtemisCest extends AbstractCest
{
    /**
     * Test basic ActiveMQ Artemis functionality
     *
     * @param        CliTester $I
     * @param        Example   $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       TaskException
     */
    public function testActivemqArtemis(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();

        // Test that ActiveMQ Artemis container is running and healthy
        $I->runDockerComposeCommand('ps');
        $I->seeInOutput('activemq-artemis');
        $I->seeInOutput('(healthy)');

        // Test network connectivity
        $this->testNetworkConnectivity($I);

        // Test ActiveMQ Artemis CLI functionality
        $this->testArtemisCLI($I);

        // Test message producer/consumer functionality
        $this->testMessageQueuing($I);

        // Test environment variables
        $this->testEnvironmentVariables($I);
    }

    /**
     * Test network connectivity to ActiveMQ Artemis ports
     *
     * @param CliTester $I
     * @return void
     */
    private function testNetworkConnectivity(CliTester $I): void
    {
        // Test ActiveMQ Artemis web console accessibility (port 8161) using curl instead of nc
        $I->runDockerComposeCommand('exec -T fpm curl -f -s http://activemq-artemis.magento2.docker:8161/ > /dev/null');
        
        // Test ActiveMQ Artemis broker port accessibility (port 61616) using telnet timeout
        $I->runDockerComposeCommand('exec -T fpm timeout 5 bash -c "</dev/tcp/activemq-artemis.magento2.docker/61616"');
        
        // Test ActiveMQ Artemis STOMP port accessibility (port 61613)
        $I->runDockerComposeCommand('exec -T fpm timeout 5 bash -c "</dev/tcp/activemq-artemis.magento2.docker/61613"');
        
        // Test that ActiveMQ Artemis is accessible through direct host name
        $I->runDockerComposeCommand('exec -T fpm timeout 5 bash -c "</dev/tcp/activemq-artemis/61616"');
    }

    /**
     * Test ActiveMQ Artemis CLI functionality
     *
     * @param CliTester $I
     * @return void
     */
    private function testArtemisCLI(CliTester $I): void
    {
        // Test ActiveMQ Artemis broker status using artemis CLI
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue stat --user admin --password admin'
        );
        $I->seeInOutput('Connection brokerURL');
        
        // Test that we can see the default system queues
        $I->seeInOutput('DLQ');
        $I->seeInOutput('ExpiryQueue');
        
        // Test broker information
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis address show --user admin --password admin'
        );
        $I->seeInOutput('DLQ');
    }

    /**
     * Test message producer/consumer functionality
     *
     * @param CliTester $I
     * @return void
     */
    private function testMessageQueuing(CliTester $I): void
    {
        // Test sending a message to the default DLQ queue (which always exists)
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis producer ' .
            '--destination queue://DLQ --message-count 1 --message "Hello ActiveMQ Artemis Test" ' .
            '--user admin --password admin'
        );
        $I->seeInOutput('Produced: 1 messages');
        
        // Test consuming the message from the DLQ queue
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis consumer ' .
            '--destination queue://DLQ --message-count 1 --user admin --password admin'
        );
        $I->seeInOutput('Consumed: 1 messages');
        
        // Test broker memory and connection info
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue stat --user admin --password admin'
        );
        $I->seeInOutput('Connection brokerURL');
    }

    /**
     * Test environment variables
     *
     * @param CliTester $I
     * @return void
     */
    private function testEnvironmentVariables(CliTester $I): void
    {
        // Test that environment variables are properly set
        $I->runDockerComposeCommand('exec -T activemq-artemis env | grep ARTEMIS');
        $I->seeInOutput('ARTEMIS_USER=admin');
        $I->seeInOutput('ARTEMIS_PASSWORD=admin');
    }

    /**
     * Builds build:compose command from given test data
     *
     * @param  Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
         return sprintf(
             '--mode=production --activemq-artemis=%s --no-es --no-os --no-redis',
             $data['version']
         );
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '2.42.0',
            ],
        ];
    }
}
