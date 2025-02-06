<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Integration;

use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\Command\BuildCustomCompose;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Source\SourceFactory;
use Magento\CloudDocker\Filesystem\Filesystem;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use ReflectionException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

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
            $filesystem->get($directory . '/docker-compose.exp.yml'),
            $filesystem->get($directory . '/docker-compose.yml')
        );
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function buildDataProvider(): array
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
                                ],
                                'mailhog' => [
                                    'smtp_port' => '1026',
                                    'http_port' => '8026'
                                ]
                            ],
                            'services' => [
                                'php' => [
                                    'version' => '8.0',
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
                                'mailhog' => [
                                    'enabled' => true,
                                ]
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
            ],
            'cloud-base-native' => [
                __DIR__ . '/_files/custom_cloud_base_native',
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
                                ],
                                'mailhog' => [
                                    'smtp_port' => '1026',
                                    'http_port' => '8026'
                                ],
                                'sync_mode' => 'native'
                            ],
                            'services' => [
                                'php' => [
                                    'version' => '8.0',
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
                                'mailhog' => [
                                    'enabled' => true,
                                ]
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
            ],
            'cloud-base-with-custom-images' => [
                __DIR__ . '/_files/custom_cloud_custom_images',
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
                                ],
                                'mailhog' => [
                                    'smtp_port' => '1026',
                                    'http_port' => '8026'
                                ],
                                'nginx' => [
                                    'worker_processes' => 'auto',
                                    'worker_connections' => 4096
                                ]
                            ],
                            'services' => [
                                'php' => [
                                    'image' => 'php-v1',
                                    'version' => '7.4',
                                    'enabled' => true,
                                    'extensions' => [
                                        'enabled' => ['xsl']
                                    ],
                                ],
                                'php-cli' => [
                                    'image-pattern' => '%s:%s-cli',
                                ],
                                'php-fpm' => [
                                    'image-pattern' => '%s:%s-fpm',
                                ],
                                'mysql' => [
                                    'image' => 'mariadb-v1',
                                    'version' => '10.2',
                                    'image-pattern' => '%s:%s',
                                    'enabled' => true,
                                ],
                                'mailhog' => [
                                    'enabled' => true,
                                ],
                                'redis' => [
                                    'image' => 'redis-v1',
                                    'enabled' => 'true',
                                    'version' => '5',
                                ],
                                'elasticsearch' => [
                                    'image' => 'elasticsearch-v1',
                                    'image-pattern' => '%s:%s',
                                    'enabled' => true,
                                    'version' => '7.6',
                                ],
                                'varnish' => [
                                    'image' => 'varnish-v1',
                                    'image-pattern' => '%s:%s',
                                    'enabled' => true,
                                    'version' => '6.2',
                                ],
                                'nginx' => [
                                    'image' => 'nginx-v1',
                                    'version' => '1.24',
                                    'image-pattern' => '%s:%s',
                                    'enabled' => 'true',
                                ],
                                'test' => [
                                    'enabled' => true,
                                ]
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
            ],
            'without TLS service' => [
                __DIR__ . '/_files/custom_cloud_no_tls_service',
                [
                    [
                        BuildCustomCompose::ARG_SOURCE,
                        json_encode([
                            'name' => 'magento',
                            'system' => [
                                'mode' => 'production',
                                'nginx' => [
                                    'worker_processes' => 4,
                                    'worker_connections' => 2048
                                ]
                            ],
                            'services' => [
                                'php' => [
                                    'enabled' => true,
                                    'version' => '8.0',
                                ],
                                'mysql' => [
                                    'enabled' => true,
                                    'version' => '10.0',
                                ],
                                'tls' => ['enabled' => false],
                            ],
                        ])
                    ]
                ]
            ],
            'without Varnish service' => [
                __DIR__ . '/_files/custom_cloud_no_varnish_service',
                [
                    [
                        BuildCustomCompose::ARG_SOURCE,
                        json_encode([
                            'name' => 'magento',
                            'system' => ['mode' => 'production'],
                            'services' => [
                                'php' => [
                                    'enabled' => true,
                                    'version' => '8.0',
                                ],
                                'mysql' => [
                                    'enabled' => true,
                                    'version' => '10.0',
                                ],
                                'varnish' => ['enabled' => false],
                            ],
                        ])
                    ]
                ]
            ],
            'without Varnish and TLS services' => [
                __DIR__ . '/_files/custom_cloud_no_varnish_and_tls_services',
                [
                    [
                        BuildCustomCompose::ARG_SOURCE,
                        json_encode([
                            'name' => 'magento',
                            'system' => ['mode' => 'production'],
                            'services' => [
                                'php' => [
                                    'enabled' => true,
                                    'version' => '8.0',
                                ],
                                'mysql' => [
                                    'enabled' => true,
                                    'version' => '10.0',
                                ],
                                'varnish' => ['enabled' => false],
                                'tls' => ['enabled' => false],
                            ],
                        ])
                    ]
                ]
            ],
        ];
    }
}
