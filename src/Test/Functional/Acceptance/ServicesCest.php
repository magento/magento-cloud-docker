<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php73
 */
class ServicesCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.3.4';

    public function _before(\CliTester $I): void
    {
        // Do nothing
    }

    public function _after(\CliTester $I): void
    {
        // Do nothing
    }

    /**
     * @var integer
     */
    protected static $counter = 0;

    /**
     * @var boolean
     */
    protected static $beforeShouldRun = true;

    /**
     * @param \CliTester $I
     * @param \Codeception\Example $data
     * @throws \Exception
     * @dataProvider servicesDataProvider
     */
    public function testServices(\CliTester $I, \Codeception\Example $data): void
    {
        if (self::$beforeShouldRun) {
            parent::_before($I);
            self::$beforeShouldRun = false;
        }

        self::$counter++;

        try {
            $I->assertTrue($I->generateDockerCompose('--mode=production ' . $data['options']));
            $I->assertTrue($I->replaceImagesWithCurrentDockerVersion());
            $I->assertTrue($I->startEnvironment());
            $I->assertTrue($I->runBashCommand('docker ps'));
            $I->seeInOutput($data['expectedResult']);
            $I->stopEnvironment();
            if (isset($data['notExpectedResult'])) {
                $I->doNotSeeInOutput($data['notExpectedResult']);
            }
        } catch (\Exception $exception) {
            parent::_after($I);
            self::$beforeShouldRun = true;
            throw $exception;
        }

        if (self::$counter === $this->getVariantsCount()) {
            parent::_after($I);
        }
    }

    /**
     * @return int
     */
    protected function getVariantsCount(): int
    {
        return sizeof($this->servicesDataProvider());
    }

    /**
     * @return array
     */
    protected function servicesDataProvider(): array
    {
        return [
            'Default' => [
                'options' => '',
                'expectedResult' => [
                    'redis:5.0',
                    'magento/magento-cloud-docker-varnish:6.2-1.3',
                    'magento/magento-cloud-docker-nginx:1.19-1.3',
                    'magento/magento-cloud-docker-php:7.3-fpm-1.3',
                    'magento/magento-cloud-docker-elasticsearch:6.5-1.3',
                    'mariadb:10.2'
                ],
                'notExpectedResult' => ['rabbitmq', 'selenium/standalone-chrome:latest', 'cron'],
            ],
            'Redis 3.2, MariaDB 10.1, php 7.2, rmq 3.5' => [
                'options' => '--redis=3.2 --db=10.1 --php=7.2 --rmq=3.5',
                'expectedResult' => [
                    'redis:3.2',
                    'magento/magento-cloud-docker-varnish:6.2-1.3',
                    'magento/magento-cloud-docker-nginx:1.19-1.3',
                    'magento/magento-cloud-docker-php:7.2-fpm-1.3',
                    'magento/magento-cloud-docker-elasticsearch:6.5-1.3',
                    'mariadb:10.1',
                    'rabbitmq:3.5'
                ],
                'notExpectedResult' => ['selenium', 'cron'],
            ],
            'Redis 4.0, MariaDB 10.2, php 7.4, rmq 3.6' => [
                'options' => '--redis=4.0 --db=10.2 --php=7.4 --rmq=3.6',
                'expectedResult' => [
                    'redis:4.0',
                    'magento/magento-cloud-docker-varnish:6.2-1.3',
                    'magento/magento-cloud-docker-nginx:1.19-1.3',
                    'magento/magento-cloud-docker-php:7.4-fpm-1.3',
                    'magento/magento-cloud-docker-elasticsearch:6.5-1.3',
                    'mariadb:10.2',
                    'rabbitmq:3.6'
                ],
                'notExpectedResult' => ['selenium', 'cron'],
            ],
            'With cron and selenium' => [
                'options' => '--with-cron --with-selenium',
                'expectedResult' => [
                    'redis:5.0',
                    'magento/magento-cloud-docker-varnish:6.2-1.3',
                    'magento/magento-cloud-docker-nginx:1.19-1.3',
                    'magento/magento-cloud-docker-php:7.3-fpm-1.3',
                    'magento/magento-cloud-docker-elasticsearch:6.5-1.3',
                    'mariadb:10.2',
                    'cron',
                    'selenium/standalone-chrome:latest'
                ],
                'notExpectedResult' => ['rabbitmq'],
            ],
        ];
    }
}
