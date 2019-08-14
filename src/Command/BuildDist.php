<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates .dist files.
 */
class BuildDist extends Command
{
    const NAME = 'build:dist';

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @param Generator $distGenerator
     */
    public function __construct(Generator $distGenerator)
    {
        $this->distGenerator = $distGenerator;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setName(self::NAME)
            ->setDescription('Generates Docker .dist files');
    }

    /**
     * {@inheritDoc}
     *
     * @throws ConfigurationMismatchException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $this->distGenerator->generate();

        $output->writeln('<info>Dist files generated</info>');
    }
}
