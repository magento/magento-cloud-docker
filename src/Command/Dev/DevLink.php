<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Dev;

use Magento\CloudDocker\Command\Linker;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Yaml\Yaml;

class DevLink extends Command
{
    private const NAME = 'dev:link';

    private const OPTION_SKIP_CLEANUP = 'skip-cleanup';

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Linker
     */
    private $linker;

    /**
     * @var ComposerUpdater
     */
    private $composerManager;

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param Linker $linker
     * @param DevBuild $updater
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        Linker $linker,
        ComposerUpdater $composerManager
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->linker = $linker;
        $this->composerManager = $composerManager;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->addOption(
                self::OPTION_SKIP_CLEANUP, 'sc',
                InputOption::VALUE_NONE,
                'Skip clean-up'
            );
    }

    /**
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int|void
     *
     * @throws FilesystemException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $configFile = $this->directoryList->getConfig();

        if (!$this->filesystem->exists($configFile)) {
            throw new FilesystemException('Config file not exists');
        }

        $this->composerManager->clean();

        $config = Yaml::parseFile($configFile);

        foreach ($config['repo'] as $repo) {
            $repoDirectory = $this->directoryList->getRepoRoot() . '/' . $repo['name'];

            $output->writeln(sprintf('Cloning %s', $repo['name']));

            if (!$input->getOption(self::OPTION_SKIP_CLEANUP)) {
                if ($this->filesystem->exists($repoDirectory)) {
                    $this->filesystem->deleteDirectory($repoDirectory);
                }

                $this->filesystem->makeDirectory($repoDirectory);

                $process = Process::fromShellCommandline(
                    sprintf(
                        'git clone --single-branch --depth=1 %s %s',
                        $repo['url'],
                        $repoDirectory
                    ),
                    $this->directoryList->getMagentoRoot()
                );
                $process->mustRun();
            }

            if (!empty($repo['base'])) {
                $output->writeln(sprintf('Linking %s', $repo['name']));

                $this->linker->link($repoDirectory, $this->directoryList->getMagentoRoot());
            }
        }

        $output->writeln('<info>Done.</info>');
    }
}
