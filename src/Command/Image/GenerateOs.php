<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Image;

use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates os images from template
 */
class GenerateOs extends Command
{
    private const NAME = 'image:generate:os';

    /**
     * Configuration map for generating es images data
     *
     * @var array
     */
    private $versionMap = [
        '1.1' => [
            'real-version' => '1.1.0',
        ],
        '1.2' => [
            'real-version' => '1.2.1',
        ],
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(Filesystem $filesystem, DirectoryList $directoryList)
    {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;

        parent::__construct();
    }

    /**
     * @inheritDoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Generates opensearch configs');

        parent::configure();
    }

    /**
     * Generates data for opensearch images.
     *
     * {@inheritDoc}
     * @throws FileNotFoundException
     * @throws \Magento\CloudDocker\Filesystem\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->versionMap as $version => $versionData) {
            $destination = $this->directoryList->getImagesRoot() . '/opensearch/' . $version;
            $dataDir = $this->directoryList->getImagesRoot() . '/opensearch/os/';
            $dockerfile = $destination . '/Dockerfile';

            $this->filesystem->deleteDirectory($destination);
            $this->filesystem->makeDirectory($destination);
            $this->filesystem->copyDirectory($dataDir, $destination);
            $this->filesystem->chmod($destination . '/docker-entrypoint.sh', 0755);

            $this->filesystem->put(
                $dockerfile,
                strtr(
                    $this->filesystem->get($dockerfile),
                    [
                        '{%version%}' => $versionData['real-version'],
                    ]
                )
            );
        }

        $output->writeln('<info>Done</info>');

        return Cli::SUCCESS;
    }
}
