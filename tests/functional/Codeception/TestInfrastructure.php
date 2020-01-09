<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Codeception;

/**
 * The module to work with test infrastructure
 */
class TestInfrastructure extends BaseModule
{
    /**
     * Creates the work directory
     *
     * @return bool
     */
    public function createWorkDir(): bool
    {
        return $this->taskFilesystemStack()
            ->stopOnFail()
            ->mkdir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Creates the directory for composer artifacts
     *
     * @return bool
     */
    public function createArtifactsDir(): bool
    {
        return $this->taskFilesystemStack()
            ->stopOnFail()
            ->mkdir($this->getArtifactsDir())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Removes the work directory
     *
     * @return bool
     */
    public function removeWorkDir(): bool
    {
        return $this->taskDeleteDir($this->getWorkDirPath())
            ->run()
            ->stopOnFail()
            ->wasSuccessful();
    }

    /**
     * Cleans up the work directory
     *
     * @return bool
     */
    public function cleanupWorkDir(): bool
    {
        if (file_exists($this->getWorkDirPath()))
        {
            $this->removeWorkDir();
        }

        return $this->createWorkDir();
    }


    /**
     * Clones cloud template to the work directory
     *
     * @param string $branch
     * @return bool
     */
    public function cloneTemplateToWorkDir(string $branch = 'master'): bool
    {
        return $this->taskGitStack()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->stopOnFail()
            ->printOutput($this->_getConfig('printOutput'))
            ->cloneRepo($this->_getConfig('template_repo'), '.', $branch)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Starts docke-sync
     *
     * @return bool
     */
    public function startDockerSync(): bool
    {
        return $this->taskExec('docker-sync')
            ->arg('start')
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Stops docker-sync
     *
     * @return bool
     */
    public function stopDockerSync(): bool
    {
        return $this->taskExec('docker-sync')
            ->arg('stop')
            ->dir($this->getWorkDirPath())
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Creates auth.json file in the work directory
     *
     * @return bool
     */
    public function createAuthJson(): bool
    {
        $auth = [
            'http-basic' => [
                'repo.magento.com' => [
                    'username' => getenv('REPO_USERNAME'),
                    'password' => getenv('REPO_PASSWORD'),
                ]
            ],
        ];

        if (getenv('GITHUB_TOKEN')) {
            $auth['github-oauth'] = [
                'github.com' => getenv('GITHUB_TOKEN'),
            ];
        }

        return $this->taskWriteToFile($this->getWorkDirPath() . '/auth.json')
            ->line(json_encode($auth))
            ->run()
            ->wasSuccessful();
    }

    /**
     * Creates ZIP file with magento-cloud-docker code
     *
     * @return bool
     */
    public function createEceDockerArtifact(): bool
    {
        $skipFiles = ['..', '.', 'vendor', '.git', '_workdir', 'vendor', 'composer.lock'];
        $files = [];

        foreach (array_diff(scandir(codecept_root_dir()), $skipFiles) as $file) {
            $files[$file] = codecept_root_dir($file);
        }

        return $this->taskPack($this->getArtifactsDir() . '/docker.zip')
            ->add($files)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Adds repo with artifacts to composer.json
     *
     * @return bool
     */
    public function addArtifactsRepoToComposer(): bool
    {
        return $this->taskComposerConfig()
            ->set('repositories.artifacts', json_encode(
                [
                    'type' => 'artifact',
                    'url' => self::ARTIFACTS_DIR,
                ],
                JSON_UNESCAPED_SLASHES
            ))->noInteraction()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Adds magento-cloud-docker to require section in composer.json
     *
     * @param string $version
     * @return bool
     */
    public function addArtifactEceDockerToComposer(string $version = '1.1.0'): bool
    {
        return $this->taskComposerRequire('composer')
            ->dependency('magento/magento-cloud-docker', $version)
            ->noInteraction()
            ->option('--no-update')
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Adds ece-docker repo to composer.json
     *
     * @return bool
     */
    public function addEceDockerGitRepoToComposer(): bool
    {
        return $this->taskComposerConfig()
            ->set('repositories.ece-docker', json_encode(
                [
                    'type' => 'vcs',
                    'url' => $this->_getConfig('ece_docker_repo')
                ]
            ))->noInteraction()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Runs bash command
     *
     * @param string $command
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function runBashCommand(string $command): bool
    {
        return $this->taskExecStack()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->exec($command)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Runs ece-docker commands
     *
     * @param string $command
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function runEceDockerCommand(string $command): bool
    {
        return $this->taskExecStack()
            ->stopOnFail()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->exec(sprintf('./vendor/bin/ece-docker %s', $command))
            ->run()
            ->wasSuccessful();
    }

    /**
     * Runs composer update
     *
     * @return bool
     */
    public function composerUpdate(): bool
    {
        return $this->taskComposerUpdate('composer')
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }
}
