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
     *
     * @throws GenericException
     * @throws ReflectionException
     *
     * @dataProvider buildDataProvider
     */
    public function testBuild(string $directory): void
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
            ->willReturnMap([
                [CliSource::OPTION_MODE, BuilderFactory::BUILDER_PRODUCTION]
            ]);
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
                __DIR__ . '/_files/cloud_base'
            ]
        ];
    }
}
