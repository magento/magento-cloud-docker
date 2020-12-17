<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Integration;

use Magento\CloudDocker\Command\BuildCompose;
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
class BuildComposeTest extends TestCase
{
    /**
     * @param string $directory
     * @param array $options
     *
     * @throws GenericException
     * @throws ReflectionException
     *
     * @dataProvider buildDataProvider
     */
    public function testBuild(string $directory, array $options): void
    {
        $container = Container::getInstance(__DIR__ . '/_files', $directory);
        /** @var Filesystem $filesystem */
        $filesystem = $container->get(Filesystem::class);

        $command = new BuildCompose(
            $container->get(BuilderFactory::class),
            $container->get(Generator::class),
            $container->get(ConfigFactory::class),
            $container->get(Filesystem::class),
            $container->get(SourceFactory::class)
        );

        /** @var MockObject|InputInterface $inputMock */
        $inputMock = $this->getMockForAbstractClass(InputInterface::class);

        $inputMock->method('getOption')
            ->willReturnMap($options);
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
     */
    public function buildDataProvider(): array
    {
        return [
            'cloud-base' => [
                __DIR__ . '/_files/cloud_base',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_WITH_ENTRYPOINT, true],
                    [CliSource::OPTION_WITH_MARIADB_CONF, true]
                ]
            ],
            'cloud-base-developer' => [
                __DIR__ . '/_files/cloud_base_developer',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_DEVELOPER],
                    [CliSource::OPTION_WITH_ENTRYPOINT, true],
                    [CliSource::OPTION_WITH_MARIADB_CONF, true]
                ]
            ],
            'cloud-base-mftf' => [
                __DIR__ . '/_files/cloud_base_mftf',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_WITH_SELENIUM, true],
                    [CliSource::OPTION_WITH_TEST, true],
                    [CliSource::OPTION_WITH_CRON, true],
                    [CliSource::OPTION_WITH_XDEBUG, true],
                    [CliSource::OPTION_ES, '5.2'],
                    [CliSource::OPTION_NO_ES, true],
                    [CliSource::OPTION_DB_INCREMENT_INCREMENT, 3],
                    [CliSource::OPTION_DB_INCREMENT_OFFSET, 2],
                    [CliSource::OPTION_WITH_ENTRYPOINT, true],
                    [CliSource::OPTION_WITH_MARIADB_CONF, true],
                    [CliSource::OPTION_TLS_PORT, '4443'],
                    [CliSource::OPTION_NO_MAILHOG, true],
                    [CliSource::OPTION_NGINX_WORKER_PROCESSES, '8'],
                    [CliSource::OPTION_NGINX_WORKER_CONNECTIONS, '4096'],
                ]
            ],
            'cloud-base-test' => [
                __DIR__ . '/_files/cloud_base_test',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_WITH_TEST, true],
                    [CliSource::OPTION_WITH_CRON, true],
                    [CliSource::OPTION_WITH_XDEBUG, true],
                    [CliSource::OPTION_ES, '5.2'],
                    [CliSource::OPTION_NO_ES, true],
                    [CliSource::OPTION_DB_INCREMENT_INCREMENT, 3],
                    [CliSource::OPTION_DB_INCREMENT_OFFSET, 2],
                    [CliSource::OPTION_WITH_ENTRYPOINT, true],
                    [CliSource::OPTION_WITH_MARIADB_CONF, true],
                    [CliSource::OPTION_TLS_PORT, '4443'],
                    [CliSource::OPTION_NO_MAILHOG, true],
                    [CliSource::OPTION_NGINX_WORKER_PROCESSES, 'auto'],
                ]
            ],
            'without TLS service' => [
                __DIR__ . '/_files/cloud_no_tls_service',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_NO_TLS, true],
                ]
            ],
            'without Varnish service' => [
                __DIR__ . '/_files/cloud_no_varnish_service',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_NO_VARNISH, true],
                ]
            ],
            'without Varnish and TLS services' => [
                __DIR__ . '/_files/cloud_no_varnish_and_tls_service',
                [
                    [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION],
                    [CliSource::OPTION_NO_VARNISH, true],
                    [CliSource::OPTION_NO_TLS, true],
                ]
            ],
        ];
    }
}
