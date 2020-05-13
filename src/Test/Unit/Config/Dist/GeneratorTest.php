<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config\Dist;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Dist\Formatter;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FilesystemException;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;
use Magento\CloudDocker\Config\Environment\Encoder;

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
     * @var EnvReader|MockObject
     */
    private $envReaderMock;

    /**
     * @var Encoder|MockObject
     */
    private $envCoderMock;

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->directoryListMock = $this->createMock(DirectoryList::class);
        $this->filesystemMock = $this->createMock(Filesystem::class);
        $this->relationshipMock = $this->createMock(Relationship::class);
        $this->formatterMock = $this->createMock(Formatter::class);
        $this->envReaderMock = $this->createMock(EnvReader::class);
        $this->envCoderMock = $this->createMock(Encoder::class);

        $this->distGenerator = new Generator(
            $this->directoryListMock,
            $this->filesystemMock,
            $this->relationshipMock,
            $this->formatterMock,
            $this->envReaderMock,
            $this->envCoderMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FilesystemException
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function testGenerate(): void
    {
        /** @var MockObject|Config $config */
        $config = $this->createMock(Config::class);

        $rootDir = '/path/to/docker';
        $this->directoryListMock->expects($this->once())
            ->method('getDockerRoot')
            ->willReturn($rootDir);
        $this->relationshipMock->expects($this->once())
            ->method('get')
            ->with($config)
            ->willReturn([
                'database' => ['config'],
                'redis' => ['config'],
            ]);
        $config->expects($this->once())
            ->method('getHost')
            ->willReturn('magento2.docker');
        $config->expects($this->once())
            ->method('getPort')
            ->willReturn('80');
        $this->envReaderMock->expects($this->once())
            ->method('read')
            ->willReturn([]);
        $this->envCoderMock->expects($this->once())
            ->method('encode')
            ->with([
                'MAGENTO_CLOUD_RELATIONSHIPS' => [
                    'database' => ['config'],
                    'redis' => ['config'],
                ],
                'MAGENTO_CLOUD_ROUTES' => [
                    'http://magento2.docker/' => [
                        'type' => 'upstream',
                        'original_url' => 'http://{default}'
                    ],
                    'https://magento2.docker/' => [
                        'type' => 'upstream',
                        'original_url' => 'https://{default}'
                    ],
                ],
                'MAGENTO_CLOUD_VARIABLES' => [
                    'ADMIN_EMAIL' => 'admin@example.com',
                    'ADMIN_PASSWORD' => '123123q',
                    'ADMIN_URL' => 'admin'
                ],
                'MAGENTO_CLOUD_APPLICATION' => [
                    'hooks' => []
                ],
            ])
            ->willReturn([
                'MAGENTO_CLOUD_RELATIONSHIPS' => 'base64_relationship_value',
                'MAGENTO_CLOUD_ROUTES' => 'base64_routes_value',
                'MAGENTO_CLOUD_VARIABLES' => 'base64_variables_value',
                'MAGENTO_CLOUD_APPLICATION' => 'base64_application_value',
            ]);

        $this->formatterMock->expects($this->exactly(4))
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
                ],
                [
                    [
                        'hooks' => []
                    ],
                    2,
                    'exported_application_value',
                ]
            ]);
        $this->filesystemMock->expects($this->exactly(2))
            ->method('put')
            ->withConsecutive(
                [$rootDir . '/config.php.dist', $this->getConfigForUpdate()],
                [
                    $rootDir . '/config.env',
                    'MAGENTO_CLOUD_RELATIONSHIPS=base64_relationship_value' . PHP_EOL
                    . 'MAGENTO_CLOUD_ROUTES=base64_routes_value' . PHP_EOL
                    . 'MAGENTO_CLOUD_VARIABLES=base64_variables_value' . PHP_EOL
                    . 'MAGENTO_CLOUD_APPLICATION=base64_application_value' . PHP_EOL

                ]
            );

        $this->distGenerator->generate($config);
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
    'MAGENTO_CLOUD_APPLICATION' => base64_encode(json_encode(exported_application_value)),
];

TEXT;
    }

    /**
     * @throws ConfigurationMismatchException
     * @throws FilesystemException
     */
    public function testGenerateFileSystemException(): void
    {
        $this->expectException(ConfigurationMismatchException::class);
        $this->expectExceptionMessage('file system error');

        $this->filesystemMock->expects($this->once())
            ->method('put')
            ->willThrowException(new ConfigurationMismatchException('file system error'));

        /** @var MockObject|Config $config */
        $config = $this->createMock(Config::class);

        $this->distGenerator->generate($config);
    }
}
