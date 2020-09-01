<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Dev;

use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class DevBuild extends Command
{
    private const NAME = 'dev:build';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Filesystem $filesystem
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     */
    public function __construct(Filesystem $filesystem, FileList $fileList, DirectoryList $directoryList)
    {
        $this->filesystem = $filesystem;
        $this->fileList = $fileList;
        $this->directoryList = $directoryList;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME);
    }

    protected function execute(InputInterface $input, OutputInterface $output): void
    {
        $patterns = [
            '/*/app/code/*/*',
            '/*/lib/internal/Magento/Framework',
            '/*/lib/internal/Magento/Framework/*'
        ];

        $config = [
            'repositories' => [],
            'require' => []
        ];

        foreach ($patterns as $pattern) {
            foreach (glob($this->directoryList->getMagentoRootDev() . $pattern, GLOB_NOSORT | GLOB_ONLYDIR) as $path) {
                $composerPath = $path . '/composer.json';

                $name = 'dev-' . json_decode($this->filesystem->get($composerPath))->name;

                $config['repositories'][$name] = [
                    'type' => 'path',
                    'url' => str_replace($this->directoryList->getMagentoRoot(), '.', $path)
                ];
                $config['require'][$name] = '*@dev';
            }
        }

        $output->writeln('Updating composer.json');

        $config = array_replace_recursive(
            json_decode($this->filesystem->get($this->directoryList->getMagentoRoot() . '/composer.json'), true),
            $config
        );

        $this->filesystem->put(
            $this->directoryList->getMagentoRoot() . '/composer.json',
            json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
