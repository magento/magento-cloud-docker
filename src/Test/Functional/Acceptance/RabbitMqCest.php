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
 * Adobe Commerce / Magento Open Source version to RabbitMQ image tag mapping
 * (see release compatibility; test lines use {@see AbstractCest::TEMPLATE_VERSION}):
 *
 * - `'>=2.4.3-p3 <2.4.5-p3 || ~2.3.7-p4'` => `'~3.5.0 || ~3.7.0 || ~3.8.0 || ~3.9.0'`
 * - `'>=2.4.5-p3 <2.4.5-p13 || >=2.4.6 <2.4.6-p6'` => `'~3.9.0 || ~3.11.0'`
 * - `'>=2.4.6-p6 <2.4.6-p11 || >=2.4.7 <2.4.7-p6'` => `'~3.12.0 || ~3.13.0'`
 * - `'>=2.4.5-p13 <2.4.6 || >=2.4.6-p11 <2.4.7 || >=2.4.7-p6 <2.4.8'` => `'3.13 || 4.1 || 4.2'`
 * - `'>=2.4.8 <2.4.9-beta1'` => `'>4.0 <=4.2'`
 * - `'>=2.4.9-beta1'` => `'4.1 || 4.2'`
 *
 * Each PHP line Cest (RabbitMq81Cest–RabbitMq85Cest) must implement {@see dataProvider()}.
 *
 * Data rows may use a bare tag (e.g. 4.2) per compatibility mapping, and/or the same line with the
 * "-management" suffix to also validate the HTTP management API on port 15672.
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
        if ($this->isManagementImage($data['version'])) {
            $this->testManagementApi($I);
        }
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
     * Test HTTP management API (management image variant only; port 15672).
     *
     * Call from the fpm container: official rabbitmq:*-management images (4.x) do not ship curl,
     * so exec inside rabbitmq yields no usable output for this check.
     *
     * @param CliTester $I
     * @return void
     */
    private function testManagementApi(CliTester $I): void
    {
        $I->runDockerComposeCommand(
            'exec -T fpm curl -fsS -u guest:guest http://rabbitmq:15672/api/overview'
        );
        $I->seeInOutput('rabbitmq_version');
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
     * Whether the Docker tag is a management image (exposes HTTP API on 15672).
     *
     * @param string $version
     * @return bool
     */
    private function isManagementImage(string $version): bool
    {
        return str_ends_with($version, '-management');
    }

    /**
     * RabbitMQ image tags for the current PHP / Adobe Commerce test line.
     *
     * @return array<int, array{version: string}>
     */
    abstract protected function dataProvider(): array;
}
