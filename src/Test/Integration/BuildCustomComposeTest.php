<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Integration;

use Magento\CloudDocker\Command\BuildCustomCompose;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Source\CliSource;
use Magento\CloudDocker\Config\Source\SourceFactory;
use Magento\CloudDocker\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\CloudDocker\App\GenericException;
use ReflectionException;

/**
 * @inheritDoc
 */
class BuildCustomComposeTest extends TestCase
{
    /**
     * @param string $directory
     * @param array $arguments
     *
     * @throws GenericException
     * @throws ReflectionException
     *
     * @dataProvider buildDataProvider
     */
    public function testBuild(string $directory, array $arguments): void
    {
        $container = Container::getInstance(__DIR__ . '/_files', $directory);
        /** @var Filesystem $filesystem */
        $filesystem = $container->get(Filesystem::class);

        $command = new BuildCustomCompose(
            $container->get(ConfigFactory::class),
            $container->get(BuilderFactory::class),
            $container->get(SourceFactory::class),
            $container->get(Generator::class),
            $container->get(Filesystem::class)
        );

        /** @var MockObject|InputInterface $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);

        $inputMock->method('getArgument')
            ->willReturnMap($arguments);
        /** @var MockObject|OutputInterface $outputMock */
        $outputMock = $this->getMockForAbstractClass(OutputInterface::class);

        $command->execute($inputMock, $outputMock);

        $this->assertSame(
            $filesystem->get($directory . '/docker-compose.yml'),
            $filesystem->get($directory . '/docker-compose.exp.yml')
        );
    }

    /**
     * @return array
     */
    public function buildDataProvider(): array
    {
        return [
            'cloud-base' => [
                __DIR__ . '/_files/custom_cloud_base',
                [
                    [
                        BuildCustomCompose::ARG_SOURCE,
                        json_encode([
                            'name' => 'magento',
                            'system' => [
                                'mode' => 'production',
                                'host' => 'magento2.test',
                                'port' => '8080',
                                'db' => [
                                    'increment_increment' => 3,
                                    'increment_offset' => 2
                                ]
                            ],
                            'services' => [
                                'php' => [
                                    'version' => '7.2',
                                    'enabled' => true,
                                    'extensions' => [
                                        'enabled' => ['xsl']
                                    ],
                                ],
                                'mysql' => [
                                    'version' => '10.0',
                                    'image' => 'mariadb',
                                    'enabled' => true,
                                ],
                            ],
                            'hooks' => [
                                'build' => 'set -e' . PHP_EOL
                                    . 'php ./vendor/bin/ece-tools run scenario/build/generate.xml' . PHP_EOL
                                    . 'php ./vendor/bin/ece-tools run scenario/build/transfer.xml',
                                'deploy' => 'php ./vendor/bin/ece-tools run scenario/deploy.xml',
                                'post_deploy' => 'php ./vendor/bin/ece-tools run scenario/post-deploy.xml'
                            ],
                            'mounts' => [
                                'var' => ['path' => 'var'],
                                'app-etc' => ['path' => 'app/etc',],
                                'pub-media' => ['path' => 'pub/media',],
                                'pub-static' => ['path' => 'pub/static']
                            ]
                        ])
                    ]
                ]
            ]
        ];
    }
}
