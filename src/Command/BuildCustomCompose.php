<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Source\BaseSource;
use Magento\CloudDocker\Config\Source\CloudBaseSource;
use Magento\CloudDocker\Config\Source\CustomSource;
use Magento\CloudDocker\Config\Source\SourceFactory;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Build configuration from custom-provided source.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class BuildCustomCompose extends Command
{
    private const NAME = 'build:custom:compose';

    public const ARG_SOURCE = 'source';

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var SourceFactory
     */
    private $sourceFactory;

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param ConfigFactory $configFactory
     * @param BuilderFactory $builderFactory
     * @param SourceFactory $sourceFactory
     * @param Generator $distGenerator
     * @param Filesystem $filesystem
     */
    public function __construct(
        ConfigFactory $configFactory,
        BuilderFactory $builderFactory,
        SourceFactory $sourceFactory,
        Generator $distGenerator,
        Filesystem $filesystem
    ) {
        $this->configFactory = $configFactory;
        $this->builderFactory = $builderFactory;
        $this->sourceFactory = $sourceFactory;
        $this->distGenerator = $distGenerator;
        $this->filesystem = $filesystem;

        parent::__construct(self::NAME);
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setDescription('Build from custom config')
            ->addArgument(
                self::ARG_SOURCE,
                InputArgument::REQUIRED,
                'A JSON string representing config source'
            );
    }

    /**
     * {@inheritDoc}
     *
     * @throws ConfigurationMismatchException
     * @throws FilesystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $source = json_decode($input->getArgument(self::ARG_SOURCE), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new ConfigurationMismatchException(
                sprintf('Config string can not be parsed: %s', json_last_error_msg()),
                json_last_error()
            );
        }

        $config = $this->configFactory->create([
            $this->sourceFactory->create(BaseSource::class),
            $this->sourceFactory->create(CloudBaseSource::class),
            new CustomSource($source)
        ]);

        $builder = $this->builderFactory->create($config->getMode());
        $compose = $builder->build($config);

        $this->distGenerator->generate($config);

        $this->filesystem->put(
            $builder->getPath(),
            Yaml::dump([
                'version' => $compose->getVersion(),
                'services' => $compose->getServices(),
                'volumes' => $compose->getVolumes(),
                'networks' => $compose->getNetworks()
            ], 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $output->writeln('<info>Configuration was built.</info>');

        return Cli::SUCCESS;
    }
}
