<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Service\ServiceInterface;
use PHPUnit\Framework\Attributes\DataProvider;
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
        'valkey' => [
            [
                'host' => 'cache',
                'port' => '6379'
            ]
        ],
        'elasticsearch' => [
            [
                'host' => 'elasticsearch',
                'port' => '9200',
            ],
        ],
        'opensearch' => [
            [
                'host' => 'opensearch',
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
        'activemq-artemis' => [
            [
                'host' => 'activemq-artemis',
                'port' => '61616',
                'username' => 'admin',
                'password' => 'admin',
                'web_console_port' => '8161',
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
     * Test complete relationship configuration with multiple services.
     *
     * Validates that the Relationship class correctly retrieves and formats
     * configuration for all enabled services including database, cache (Redis/Valkey),
     * search (Elasticsearch/OpenSearch), and message queue services.
     *
     * @param string $activemqArtemisVersion
     * @throws \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    #[DataProvider('activemqArtemisVersionDataProvider')]
    public function testGet(string $activemqArtemisVersion)
    {
        $mysqlVersion = '10.4';
        $redisVersion = '5.2';
        $valkeyVersion = '8.1';
        $esVersion = '7.7';
        $osVersion = '1.1';
        $rmqVersion = '3.5';
        $zookeeperVersion = 'latest';
        $configWithType = $this->defaultConfigs;
        $configWithType['database'][0]['type'] = "mysql:$mysqlVersion";
        $configWithType['redis'][0]['type'] = "redis:$redisVersion";
        $configWithType['valkey'][0]['type'] = "valkey:$valkeyVersion";
        $configWithType['elasticsearch'][0]['type'] = "elasticsearch:$esVersion";
        $configWithType['opensearch'][0]['type'] = "opensearch:$osVersion";
        $configWithType['rabbitmq'][0]['type'] = "rabbitmq:$rmqVersion";
        $configWithType['activemq-artemis'][0]['type'] = "activemq-artemis:$activemqArtemisVersion";
        $configWithType['zookeeper'][0]['type'] = "zookeeper:$zookeeperVersion";

        $this->configMock->expects($this->exactly(10))
            ->method('hasServiceEnabled')
            ->willReturnCallback(function ($service) {
                static $services = [
                    ServiceInterface::SERVICE_DB,
                    ServiceInterface::SERVICE_DB_QUOTE,
                    ServiceInterface::SERVICE_DB_SALES,
                    'redis',
                    'valkey',
                    'elasticsearch',
                    'opensearch',
                    'rabbitmq',
                    'activemq-artemis',
                    'zookeeper'
                ];

                static $responses = [
                    true,
                    false,
                    false,
                    true,
                    true,
                    true,
                    true,
                    true,
                    true,
                    true
                ];

                $expectedService = array_shift($services);
                $expectedResponse = array_shift($responses);

                $this->assertSame($expectedService, $service);

                return $expectedResponse;
            });

        $services = [
            ServiceInterface::SERVICE_DB,
            'redis',
            'valkey',
            'elasticsearch',
            'opensearch',
            'rabbitmq',
            'activemq-artemis',
            'zookeeper'
        ];
        
        $versions = [
            $mysqlVersion,
            $redisVersion,
            $valkeyVersion,
            $esVersion,
            $osVersion,
            $rmqVersion,
            $activemqArtemisVersion,
            $zookeeperVersion
            ];

        $this->configMock->expects($this->exactly(8))
            ->method('getServiceVersion')
            ->willReturnCallback(function ($service) use (
                &$services,
                &$versions
            ) {
                $expectedService = array_shift($services);
                $expectedVersion = array_shift($versions);
        
                $this->assertSame($expectedService, $service);
        
                return $expectedVersion;
            });

        $this->assertEquals($configWithType, $this->relationship->get($this->configMock));
    }

    /**
     * Supported ActiveMQ Artemis versions to validate relationship output.
     *
     * @return array<string, array{0: string}>
     */
    public static function activemqArtemisVersionDataProvider(): array
    {
        return [
            'activemq-artemis 2.17' => ['2.17'],
            'activemq-artemis 2.42.0' => ['2.42.0'],
            'activemq-artemis 2.51.0' => ['2.51.0'],
        ];
    }

    /**
     *
     * @param string $dbVersion Value from `getServiceVersion` for {@see ServiceInterface::SERVICE_DB}
     * @return void
     * @throws ConfigurationMismatchException
     */
    #[DataProvider('databaseMariaDbImageTagDataProvider')]
    public function testGetWithDatabaseMariaDbImageTag(string $dbVersion): void
    {
        $this->configMock->method('hasServiceEnabled')
            ->willReturnCallback(function ($service) {
                return $service === ServiceInterface::SERVICE_DB;
            });

        $this->configMock->method('getServiceVersion')
            ->with(ServiceInterface::SERVICE_DB)
            ->willReturn($dbVersion);

        $relationships = $this->relationship->get($this->configMock);

        $this->assertArrayHasKey('database', $relationships);
        $this->assertSame('db', $relationships['database'][0]['host']);
        $this->assertSame('3306', $relationships['database'][0]['port']);
        $this->assertSame('magento2', $relationships['database'][0]['path']);
        $this->assertSame('magento2', $relationships['database'][0]['username']);
        $this->assertSame('magento2', $relationships['database'][0]['password']);
        $this->assertSame('mysql:' . $dbVersion, $relationships['database'][0]['type']);
    }

    /**
     * MariaDB image tags exercised in functional compose tests.
     *
     * @return array<string, array{0: string}>
     */
    public static function databaseMariaDbImageTagDataProvider(): array
    {
        return [
            'mariadb 10.6' => ['10.6'],
            'mariadb 10.11' => ['10.11'],
            'mariadb 11.4' => ['11.4'],
            'mariadb 11.8' => ['11.8'],
            'mariadb 12.2' => ['12.2'],
            'mariadb 12.3-rc' => ['12.3-rc'],
        ];
    }

    /**
     * Test relationship configuration for Valkey 8.1.
     *
     * Validates that Valkey 8.1 service relationships are properly configured
     * with correct host (cache), port (6379), and type (valkey:8.1).
     *
     * @return void
     * @throws ConfigurationMismatchException
     */
    public function testGetWithValkey81(): void
    {
        $valkeyVersion = '8.1';

        $this->configMock->method('hasServiceEnabled')
            ->willReturnCallback(function ($service) {
                return $service === ServiceInterface::SERVICE_VALKEY;
            });

        $this->configMock->method('getServiceVersion')
            ->with(ServiceInterface::SERVICE_VALKEY)
            ->willReturn($valkeyVersion);

        $relationships = $this->relationship->get($this->configMock);

        $this->assertArrayHasKey('valkey', $relationships);
        $this->assertSame('cache', $relationships['valkey'][0]['host']);
        $this->assertSame('6379', $relationships['valkey'][0]['port']);
        $this->assertSame('valkey:8.1', $relationships['valkey'][0]['type']);
    }

    /**
     * Test relationship configuration for Valkey 9.
     *
     * Adobe Commerce 2.4.9 line - validates that Valkey 9 service relationships
     * are properly configured with correct host (cache), port (6379), and type (valkey:9).
     *
     * @return void
     * @throws ConfigurationMismatchException
     */
    public function testGetWithValkey9(): void
    {
        $valkeyVersion = '9';

        $this->configMock->method('hasServiceEnabled')
            ->willReturnCallback(function ($service) {
                return $service === ServiceInterface::SERVICE_VALKEY;
            });

        $this->configMock->method('getServiceVersion')
            ->with(ServiceInterface::SERVICE_VALKEY)
            ->willReturn($valkeyVersion);

        $relationships = $this->relationship->get($this->configMock);

        $this->assertArrayHasKey('valkey', $relationships);
        $this->assertSame('cache', $relationships['valkey'][0]['host']);
        $this->assertSame('valkey:9', $relationships['valkey'][0]['type']);
    }
}
