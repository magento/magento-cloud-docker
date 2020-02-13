<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\Command\BuildCompose\SplitDbOptionValidator;
use Magento\CloudDocker\Compose\ProductionBuilder;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates .dist files.
 */
class BuildDist extends Command
{
    public const NAME = 'build:dist';

    public const OPTION_SPLIT_DB = 'split-db';

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @var SplitDbOptionValidator
     */
    private $splitDbOptionValidator;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @param Generator $distGenerator
     * @param SplitDbOptionValidator $splitDbOptionValidator
     * @param ConfigFactory $configFactory
     */
    public function __construct(
        Generator $distGenerator,
        SplitDbOptionValidator $splitDbOptionValidator,
        ConfigFactory $configFactory
    ) {
        $this->distGenerator = $distGenerator;
        $this->splitDbOptionValidator = $splitDbOptionValidator;
        $this->configFactory = $configFactory;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Generates Docker .dist files')
            ->addOption(
                self::OPTION_SPLIT_DB,
                null,
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_OPTIONAL,
                'Adds additional database services for a split database architecture',
                []
            );
    }

    /**
     * {@inheritDoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     * @throws ConfigurationMismatchException
     * @throws GenericException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $splitDbTypes = $input->getOption(self::OPTION_SPLIT_DB);
        $this->splitDbOptionValidator->validate($splitDbTypes);
        $config = $this->configFactory->create();
        $config->set(ProductionBuilder::SPLIT_DB, $splitDbTypes);

        $this->distGenerator->generate($config);

        $output->writeln('<info>Dist files generated</info>');
    }
}
