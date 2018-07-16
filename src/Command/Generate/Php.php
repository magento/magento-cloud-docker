<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Mcd\Command\Generate;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @inheritdoc
 */
class Php extends Command
{
    private const SUPPORTED_VERSIONS = ['7.0', '7.1', '7.2'];
    private const EDITIONS = ['cli', 'fpm'];
    private const ARGUMENT_VERSION = 'version';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @inheritdoc
     */
    public function __construct(?string $name = null)
    {
        $this->filesystem = new Filesystem();

        parent::__construct($name);
    }


    /**
     * @inheritdoc
     */
    protected function configure()
    {
        $this->setName('generate:php')
            ->setDescription('Generates proper configs')
            ->addArgument(self::ARGUMENT_VERSION, InputArgument::REQUIRED);

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException
     * @throws FileNotFoundException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $version = $input->getArgument(self::ARGUMENT_VERSION);

        if (!\in_array($version, self::SUPPORTED_VERSIONS, true)) {
            throw new \InvalidArgumentException('Not supported version');
        }

        foreach (self::EDITIONS as $edition) {
            $this->copyData($version, $edition);
        }

        $output->writeln('<info>Done</info>');
    }

    /**
     * @param string $version
     * @param string $edition
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    private function copyData(string $version, string $edition)
    {
        $destination = BP . '/' . $version . '-' . $edition;
        $dataDir = DATA . '/php-' . $edition;

        $this->filesystem->deleteDirectory($destination);
        $this->filesystem->makeDirectory($destination);
        $this->filesystem->copyDirectory($dataDir, $destination);

        $dockerfile = $destination . '/Dockerfile';
        $content = strtr(
            $this->filesystem->get($dockerfile),
            [
                '{%version%}' => $version,
            ]
        );

        $this->filesystem->put($dockerfile, $content);
    }
}
