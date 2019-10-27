<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config;

use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\Config\Reader;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Yaml\Yaml;

/**
 * @inheritDoc
 */
class ReaderTest extends TestCase
{
    /**
     * @var Reader
     */
    private $reader;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);

        $this->fileListMock->method('getAppConfig')
            ->willReturn('/root/.magento.app.yaml');
        $this->fileListMock->method('getServicesConfig')
            ->willReturn('/root/.magento/services.yaml');

        $this->reader = new Reader(
            $this->fileListMock,
            $this->filesystemMock
        );
    }

    public function testReadEmpty()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('PHP version could not be parsed.');

        $this->filesystemMock->expects($this->exactly(2))
            ->method('get')
            ->willReturn(Yaml::dump([]));

        $this->reader->read();
    }

    public function testReadWithPhp()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('Relationships could not be parsed.');

        $this->filesystemMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                ['/root/.magento.app.yaml', false, Yaml::dump(['type' => 'php:7.1'])],
                ['/root/.magento/services.yaml', false, Yaml::dump([])]
            ]);

        $this->reader->read();
    }

    public function testReadWithMultipleSameServices()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('Only one instance of service "elasticsearch" supported');

        $this->filesystemMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'elasticsearch5' => 'elasticsearch5:elasticsearch'
                        ]
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                        'elasticsearch5' => [
                            'type' => 'elasticsearch:5.2',
                            'disk' => '1024'
                        ]
                    ])
                ]
            ]);

        $this->assertSame([
            'type' => 'php:7.1',
            'crons' => [],
            'services' => [
                'mysql' => [
                    'service' => 'mysql',
                    'version' => '10.0'
                ],
                'redis' => [
                    'service' => 'redis',
                    'version' => '3.0'
                ],
                'elasticsearch' => [
                    'service' => 'elasticsearch',
                    'version' => '1.4'
                ],
                'rabbitmq' => [
                    'service' => 'rabbitmq',
                    'version' => '3.5'
                ]
            ]
        ], $this->reader->read());
    }

    public function testReadWithMissedService()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('Service with name "myrabbitmq" could not be parsed');

        $this->filesystemMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'mq' => 'myrabbitmq:rabbitmq'
                        ]
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                    ])
                ]
            ]);

        $this->reader->read();
    }

    public function testReadBroken()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('Some error');

        $this->fileListMock->expects($this->once())
            ->method('getAppConfig')
            ->willThrowException(new \Exception('Some error'));

        $this->reader->read();
    }

    /**
     * @throws FileSystemException
     */
    public function testRead()
    {
        $this->filesystemMock->expects($this->exactly(2))
            ->method('get')
            ->willReturnMap([
                [
                    '/root/.magento.app.yaml',
                    false,
                    Yaml::dump([
                        'type' => 'php:7.1',
                        'relationships' => [
                            'database' => 'mysql:mysql',
                            'redis' => 'redis:redis',
                            'elasticsearch' => 'elasticsearch:elasticsearch',
                            'mq' => 'myrabbitmq:rabbitmq'
                        ],
                    ]),
                ],
                [
                    '/root/.magento/services.yaml',
                    false,
                    Yaml::dump([
                        'mysql' => [
                            'type' => 'mysql:10.0',
                            'disk' => '2048'
                        ],
                        'redis' => [
                            'type' => 'redis:3.0'
                        ],
                        'elasticsearch' => [
                            'type' => 'elasticsearch:1.4',
                            'disk' => '1024'
                        ],
                        'myrabbitmq' => [
                            'type' => 'rabbitmq:3.5',
                            'disk' => '1024'
                        ]
                    ])
                ]
            ]);

        $this->assertSame([
            'type' => 'php:7.1',
            'crons' => [],
            'services' => [
                'mysql' => [
                    'service' => 'mysql',
                    'version' => '10.0'
                ],
                'redis' => [
                    'service' => 'redis',
                    'version' => '3.0'
                ],
                'elasticsearch' => [
                    'service' => 'elasticsearch',
                    'version' => '1.4'
                ],
                'rabbitmq' => [
                    'service' => 'rabbitmq',
                    'version' => '3.5'
                ]
            ],
            'runtime' => [
                'extensions' => [],
                'disabled_extensions' => [],
            ]
        ], $this->reader->read());
    }
}
