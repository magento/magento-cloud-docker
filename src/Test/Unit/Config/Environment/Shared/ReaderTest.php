<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config\Environment\Shared;

use Magento\CloudDocker\Config\Environment\Shared\Reader;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FilesystemException;
use PHPUnit\Framework\MockObject\MockObject;
use Magento\CloudDocker\Config\Environment\Encoder;
use PHPUnit\Framework\TestCase;

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
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @var Encoder|MockObject
     */
    private $envCoderMock;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->envCoderMock = $this->createMock(Encoder::class);

        $this->reader = new Reader(
            $this->directoryListMock,
            $this->filesystemMock,
            $this->envCoderMock
        );
    }

    /**
     * @throws FilesystemException
     * @throws FileNotFoundException
     */
    public function testExecute()
    {
        $configFromFile = [
            'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                [
                    'ADMIN_EMAIL' => 'test2@email.com',
                    'ADMIN_USERNAME' => 'admin2',
                    'SCD_COMPRESSION_LEVEL' => '0',
                    'MIN_LOGGING_LEVEL' => 'debug',
                ]
            )),
        ];
        $expectedConfig = [
            'MAGENTO_CLOUD_VARIABLES' => [
                'ADMIN_EMAIL' => 'test2@email.com',
                'ADMIN_USERNAME' => 'admin2',
                'SCD_COMPRESSION_LEVEL' => '0',
                'MIN_LOGGING_LEVEL' => 'debug',
            ]
        ];

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->filesystemMock->expects($this->exactly(1))
            ->method('exists')
            ->with('docker_root/config.php')
            ->willReturn(true);
        $this->filesystemMock->expects($this->once())
            ->method('getRequire')
            ->willReturn($configFromFile);
        $this->envCoderMock->expects($this->once())
            ->method('decode')
            ->with($configFromFile)
            ->willReturn($expectedConfig);

        $this->assertSame($expectedConfig, $this->reader->read());
    }

    public function testExecuteNoSource()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->filesystemMock->expects($this->once())
            ->method('exists')
            ->willReturn(false);

        $this->assertSame([], $this->reader->read());
    }
}
