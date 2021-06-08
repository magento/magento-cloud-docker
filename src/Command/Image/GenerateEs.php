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
 * Generates es images from template
 */
class GenerateEs extends Command
{
    private const NAME = 'image:generate:es';

    private const SINGLE_NODE = 'RUN echo "discovery.type: single-node" >> '
    . '/usr/share/elasticsearch/config/elasticsearch.yml';

    /**
     * Configuration map for generating es images data
     *
     * @var array
     */
    private $versionMap = [
        '6.5' => [
            'real-version' => '6.5.4',
            'single-node' => false,
        ],
        '6.8' => [
            'real-version' => '6.8.15',
            'single-node' => true,
        ],
        '7.5' => [
            'real-version' => '7.5.2',
            'single-node' => true,
        ],
        '7.6' => [
            'real-version' => '7.6.2',
            'single-node' => true,
        ],
        '7.7' => [
            'real-version' => '7.7.1',
            'single-node' => true,
        ],
        '7.9' => [
            'real-version' => '7.9.3',
            'single-node' => true,
        ],
        '7.11' => [
            'real-version' => '7.11.2',
            'single-node' => true,
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
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Generates elasticsearch configs');

        parent::configure();
    }

    /**
     * Generates data for elasticsearch images.
     *
     * {@inheritDoc}
     * @throws FileNotFoundException
     * @throws \Magento\CloudDocker\Filesystem\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->versionMap as $version => $versionData) {
            $destination = $this->directoryList->getImagesRoot() . '/elasticsearch/' . $version;
            $dataDir = $this->directoryList->getImagesRoot() . '/elasticsearch/es/';
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
                        '{%single_node%}' => $versionData['single-node'] ? self::SINGLE_NODE : '',
                    ]
                )
            );
        }

        $output->writeln('<info>Done</info>');

        return Cli::SUCCESS;
    }
}
