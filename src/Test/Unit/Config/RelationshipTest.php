<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config;

use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Service\ServiceInterface;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class RelationshipTest extends TestCase
{
    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @var \PHPUnit\Framework\MockObject\MockObject|Config
     */
    private $configMock;

    /**
     * @var array
     */
    public $defaultConfigs = [
        'database' => [
            [
                'host' => 'db',
                'path' => 'magento2',
                'password' => 'magento2',
                'username' => 'magento2',
                'port' => '3306'
            ],
        ],
        'redis' => [
            [
                'host' => 'redis',
                'port' => '6379'
            ]
        ],
        'elasticsearch' => [
            [
                'host' => 'elasticsearch',
                'port' => '9200',
            ],
        ],
        'rabbitmq' => [
            [
                'host' => 'rabbitmq',
                'port' => '5672',
                'username' => 'guest',
                'password' => 'guest',
            ]
        ],
        'zookeeper' => [
            [
                'host' => 'zookeeper',
                'port' => '2181',
            ]
        ],
    ];

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->configMock = $this->createMock(Config::class);
        $this->relationship = new Relationship();
    }

    /**
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    public function testGet()
    {
        $mysqlVersion = '10.4';
        $redisVersion = '5.2';
        $esVersion = '7.7';
        $rmqVersion = '3.5';
        $zookeeperVersion = 'latest';
        $configWithType = $this->defaultConfigs;
        $configWithType['database'][0]['type'] = "mysql:$mysqlVersion";
        $configWithType['redis'][0]['type'] = "redis:$redisVersion";
        $configWithType['elasticsearch'][0]['type'] = "elasticsearch:$esVersion";
        $configWithType['rabbitmq'][0]['type'] = "rabbitmq:$rmqVersion";
        $configWithType['zookeeper'][0]['type'] = "zookeeper:$zookeeperVersion";

        $this->configMock->expects($this->exactly(7))
            ->method('hasServiceEnabled')
            ->withConsecutive(
                [ServiceInterface::SERVICE_DB],
                [ServiceInterface::SERVICE_DB_QUOTE],
                [ServiceInterface::SERVICE_DB_SALES],
                ['redis'],
                ['elasticsearch'],
                ['rabbitmq'],
                ['zookeeper']
            )
            ->willReturnOnConsecutiveCalls(true, false, false, true, true, true, true);
        $this->configMock->expects($this->exactly(5))
            ->method('getServiceVersion')
            ->withConsecutive(
                [ServiceInterface::SERVICE_DB],
                ['redis'],
                ['elasticsearch'],
                ['rabbitmq'],
                ['zookeeper']
            )
            ->willReturnOnConsecutiveCalls(
                $mysqlVersion,
                $redisVersion,
                $esVersion,
                $rmqVersion,
                $zookeeperVersion
            );

        $this->assertEquals($configWithType, $this->relationship->get($this->configMock));
    }
}
