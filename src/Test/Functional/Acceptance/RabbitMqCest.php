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
 * Generic RabbitMQ tests to validate configuration and functionality within the
 * Magento Cloud Docker environment.
 *
 * Data rows use bare image tags (e.g. 4.2) for the latest supported patch on each release line.
 */
abstract class RabbitMqCest extends AbstractCest
{
    /**
     * Test basic RabbitMQ functionality
     *
     * @param        CliTester $I
     * @param        Example   $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       TaskException
     */
    public function testRabbitMq(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();

        $I->runDockerComposeCommand('ps');
        $I->seeInOutput('rabbitmq');
        $I->seeInOutput('(healthy)');

        $this->testNetworkConnectivity($I);
        $this->testRabbitMqCli($I);
        $this->testDefaultGuestCredentials($I);
    }

    /**
     * Test network connectivity to RabbitMQ AMQP (5672).
     *
     * @param CliTester $I
     * @return void
     */
    private function testNetworkConnectivity(CliTester $I): void
    {
        $I->runDockerComposeCommand(
            'exec -T fpm timeout 5 bash -c "</dev/tcp/rabbitmq.magento2.docker/5672"'
        );
        $I->runDockerComposeCommand(
            'exec -T fpm timeout 5 bash -c "</dev/tcp/rabbitmq/5672"'
        );
    }

    /**
     * Test RabbitMQ CLI diagnostics inside the broker container.
     *
     * @param CliTester $I
     * @return void
     */
    private function testRabbitMqCli(CliTester $I): void
    {
        $I->runDockerComposeCommand('exec -T rabbitmq rabbitmq-diagnostics -q ping');
        $I->seeInOutput('Ping succeeded');

        $I->runDockerComposeCommand('exec -T rabbitmq rabbitmqctl status');
        $I->seeInOutput('RabbitMQ version');

        $I->runDockerComposeCommand('exec -T rabbitmq rabbitmqctl list_vhosts');
        $I->seeInOutput('/');
    }

    /**
     * Test default guest credentials (official rabbitmq:4.x images no longer expose
     * RABBITMQ_DEFAULT_* in the container environment; defaults still apply).
     *
     * @param CliTester $I
     * @return void
     */
    private function testDefaultGuestCredentials(CliTester $I): void
    {
        $I->runDockerComposeCommand('exec -T rabbitmq rabbitmqctl authenticate_user guest guest');
        $I->seeInOutput('Success');
    }

    /**
     * Builds build:compose command from given test data.
     *
     * @param Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
        return sprintf(
            '--mode=production --rmq=%s --no-es --no-os --no-redis',
            $data['version']
        );
    }

    /**
     * RabbitMQ image tags for the current PHP / Adobe Commerce test line.
     *
     * @return array<int, array{version: string}>
     */
    abstract protected function dataProvider(): array;
}
