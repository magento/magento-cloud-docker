<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config\Dist;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Dist\Formatter;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\Compose\ProductionBuilder;

/**
 * @inheritdoc
 */
class GeneratorTest extends TestCase
{
    /**
     * @var DirectoryList|MockObject
     */
    private $directoryListMock;

    /**
     * @var Filesystem|MockObject
     */
    private $filesystemMock;

    /**
     * @var Relationship|MockObject
     */
    private $relationshipMock;

    /**
     * @var Formatter|MockObject
     */
    private $formatterMock;

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @var MockObject|Repository
     */
    private $configMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->relationshipMock = $this->createMock(Relationship::class);
        $this->formatterMock = $this->createMock(Formatter::class);
        $this->configMock = $this->createMock(Repository::class);

        $this->distGenerator = new Generator(
            $this->directoryListMock,
            $this->filesystemMock,
            $this->relationshipMock,
            $this->formatterMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testGenerate()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with(ProductionBuilder::SPLIT_DB)
            ->willReturn([]);
        $rootDir = '/path/to/docker';
        $this->directoryListMock->expects($this->once())
            ->method('getDockerRoot')
            ->willReturn($rootDir);
        $this->relationshipMock->expects($this->once())
            ->method('get')
            ->willReturn([
                'database' => ['config'],
                'redis' => ['config'],
            ]);
        $this->formatterMock->expects($this->exactly(3))
            ->method('varExport')
            ->willReturnMap([
                [
                    [
                        'database' => ['config'],
                        'redis' => ['config'],
                    ],
                    2,
                    'exported_relationship_value',
                ],
                [
                    [
                        'http://magento2.docker/' => [
                            'type' => 'upstream',
                            'original_url' => 'http://{default}'
                        ],
                        'https://magento2.docker/' => [
                            'type' => 'upstream',
                            'original_url' => 'https://{default}'
                        ],
                    ],
                    2,
                    'exported_routes_value',
                ],
                [
                    [
                        'ADMIN_EMAIL' => 'admin@example.com',
                        'ADMIN_PASSWORD' => '123123q',
                        'ADMIN_URL' => 'admin'
                    ],
                    2,
                    'exported_variables_value'
                ]
            ]);
        $this->filesystemMock->expects($this->once())
            ->method('put')
            ->with($rootDir . '/config.php.dist', $this->getConfigForUpdate());

        $this->distGenerator->generate($this->configMock);
    }

    /**
     * @return string
     */
    private function getConfigForUpdate(): string
    {
        return <<<TEXT
<?php

return [
    'MAGENTO_CLOUD_RELATIONSHIPS' => base64_encode(json_encode(exported_relationship_value)),
    'MAGENTO_CLOUD_ROUTES' => base64_encode(json_encode(exported_routes_value)),
    'MAGENTO_CLOUD_VARIABLES' => base64_encode(json_encode(exported_variables_value)),
];

TEXT;
    }

    public function testGenerateFileSystemException()
    {
        $this->configMock->expects($this->once())
            ->method('get')
            ->with(ProductionBuilder::SPLIT_DB)
            ->willReturn([]);
        $this->expectException(ConfigurationMismatchException::class);
        $this->expectExceptionMessage('file system error');

        $this->filesystemMock->expects($this->once())
            ->method('put')
            ->willThrowException(new ConfigurationMismatchException('file system error'));

        $this->distGenerator->generate($this->configMock);
    }
}
