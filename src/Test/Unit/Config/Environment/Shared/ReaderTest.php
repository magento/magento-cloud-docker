<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config\Environment\Shared;

use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\Config\Environment\Shared\Reader;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use PHPUnit\Framework\MockObject\MockObject;
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
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);

        $this->reader = new Reader($this->directoryListMock, $this->filesystemMock);
    }

    /**
     * @throws FilesystemException
     */
    public function testExecute()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->filesystemMock->expects($this->exactly(2))
            ->method('exists')
            ->with('docker_root/config.php')
            ->willReturn(true);
        $this->filesystemMock->expects($this->once())
            ->method('getRequire')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                    [
                        'ADMIN_EMAIL' => 'test2@email.com',
                        'ADMIN_USERNAME' => 'admin2',
                        'SCD_COMPRESSION_LEVEL' => '0',
                        'MIN_LOGGING_LEVEL' => 'debug',
                    ]
                )),
            ]);

        $this->reader->read();
    }

    /**
     * @throws FilesystemException
     */
    public function testExecuteUsingDist()
    {
        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->filesystemMock->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', true],
            ]);
        $this->filesystemMock->expects($this->once())
            ->method('getRequire')
            ->willReturn([
                'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(
                    [
                        'ADMIN_EMAIL' => 'test2@email.com',
                        'ADMIN_USERNAME' => 'admin2',
                        'SCD_COMPRESSION_LEVEL' => '0',
                        'MIN_LOGGING_LEVEL' => 'debug',
                    ]
                )),
            ]);

        $this->reader->read();
    }

    public function testExecuteNoSource()
    {
        $this->expectException(FilesystemException::class);
        $this->expectExceptionMessage('Source file docker_root/config.php.dist does not exists');

        $this->directoryListMock->method('getDockerRoot')
            ->willReturn('docker_root');
        $this->filesystemMock->expects($this->exactly(2))
            ->method('exists')
            ->willReturnMap([
                ['docker_root/config.php', false],
                ['docker_root/config.php.dist', false],
            ]);

        $this->reader->read();
    }
}
