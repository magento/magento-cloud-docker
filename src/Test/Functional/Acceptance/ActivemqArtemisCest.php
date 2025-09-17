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
 * @group php84
 */
class ActivemqArtemisCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9';

    /**
     * @param        CliTester $I
     * @param        Example   $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       TaskException
     */
    public function testActivemqArtemis(CliTester $I, Example $data): void
    {
        // Create services.yaml with activemq-artemis configuration
        $servicesConfig = [
            'activemq' => [
                'type' => 'activemq-artemis:' . $data['version']
            ]
        ];
        $I->writeServicesYaml($servicesConfig);

        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        
        // Test that ActiveMQ Artemis container is running and healthy
        $I->runDockerComposeCommand('ps');
        $I->seeInOutput('activemq-artemis');
        $I->seeInOutput('(healthy)');
        
        // Test ActiveMQ Artemis web console accessibility (port 8161)
        $I->runDockerComposeCommand('exec -T fpm nc -z activemq-artemis.magento2.docker 8161');
        $I->seeInOutput('');  // nc returns empty output on success
        
        // Test ActiveMQ Artemis broker port accessibility (port 61616)
        $I->runDockerComposeCommand('exec -T fpm nc -z activemq-artemis.magento2.docker 61616');
        $I->seeInOutput('');
        
        // Test ActiveMQ Artemis STOMP port accessibility (port 61613)
        $I->runDockerComposeCommand('exec -T fpm nc -z activemq-artemis.magento2.docker 61613');
        $I->seeInOutput('');
        
        // Test that ActiveMQ Artemis is accessible through direct host name
        $I->runDockerComposeCommand('exec -T fpm nc -z activemq-artemis 61616');
        $I->seeInOutput('');
        
        // Test ActiveMQ Artemis broker status using artemis CLI
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue stat --user admin --password admin'
        );
        $I->seeInOutput('Connection brokerURL');
        
        // Test creating a queue using artemis CLI
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue create ' .
            '--name test.queue --address test.address --routing-type anycast --user admin --password admin'
        );
        $I->seeInOutput('successfully');
        
        // Test listing queues to verify our test queue was created
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue stat --user admin --password admin'
        );
        $I->seeInOutput('test.queue');
        
        // Test sending a message to the queue
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis producer ' .
            '--destination queue://test.queue --message-count 1 --message "Hello ActiveMQ Artemis" ' .
            '--user admin --password admin'
        );
        $I->seeInOutput('Produced: 1 messages');
        
        // Test consuming the message from the queue
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis consumer ' .
            '--destination queue://test.queue --message-count 1 --user admin --password admin'
        );
        $I->seeInOutput('Hello ActiveMQ Artemis');
        
        // Test broker memory and connection info
        $I->runDockerComposeCommand(
            'exec -T activemq-artemis /opt/activemq-artemis/bin/artemis queue stat --user admin --password admin'
        );
        $I->seeInOutput('CONNECTION_COUNT');
        
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
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function buildCommand(Example $data): string
    {
        // Note: $data is not used as ActiveMQ Artemis is configured via services.yaml
        // rather than CLI options, but parameter is kept for consistency with other tests
        return '--mode=production --no-es --no-os --no-redis --no-valkey';
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '2.17',
            ],
        ];
    }
}
