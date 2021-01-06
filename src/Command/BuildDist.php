<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\CloudDocker\Config\Source;

/**
 * Generates .dist files.
 */
class BuildDist extends Command
{
    public const NAME = 'build:dist';

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var Source\SourceFactory
     */
    private $sourceFactory;

    /**
     * @param Generator $distGenerator
     * @param ConfigFactory $configFactory
     * @param Source\SourceFactory $sourceFactory
     */
    public function __construct(
        Generator $distGenerator,
        ConfigFactory $configFactory,
        Source\SourceFactory $sourceFactory
    ) {
        $this->distGenerator = $distGenerator;
        $this->configFactory = $configFactory;
        $this->sourceFactory = $sourceFactory;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Generates Docker .dist files');
    }

    /**
     * {@inheritDoc}
     *
     * @throws ConfigurationMismatchException
     * @throws FilesystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->configFactory->create([
            $this->sourceFactory->create(Source\BaseSource::class),
            $this->sourceFactory->create(Source\CloudBaseSource::class),
            $this->sourceFactory->create(Source\CloudSource::class)
        ]);

        $this->distGenerator->generate($config);

        $output->writeln('<info>Dist files generated</info>');

        return Cli::SUCCESS;
    }
}
