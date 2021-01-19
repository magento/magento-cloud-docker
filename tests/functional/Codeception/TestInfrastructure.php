<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Codeception;

use Composer\Factory;
use Composer\IO\NullIO;
use Symfony\Component\Yaml\Yaml;

/**
 * The module to work with test infrastructure
 */
class TestInfrastructure extends BaseModule
{
    private const USE_CACHED_WORKDIR_OPTION = 'use_cached_workdir';

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
        if (file_exists($this->getWorkDirPath())) {
            $this->removeWorkDir();
        }

        return $this->createWorkDir();
    }

    /**
     * @param string $version
     * @return bool
     */
    public function isCacheWorkDirExists(string $version): bool
    {
        return $this->_getConfig(self::USE_CACHED_WORKDIR_OPTION) && is_dir($this->getCachedWorkDirPath($version));
    }

    /**
     * @param string $version
     * @return void
     */
    public function cacheWorkDir(string $version): void
    {
        if (!$this->_getConfig(self::USE_CACHED_WORKDIR_OPTION)) {
            return;
        }

        $this->copyDir($this->getWorkDirPath(), $this->getCachedWorkDirPath($version));
    }

    /**
     * @param string $version
     */
    public function restoreWorkDirFromCache(string $version): void
    {
        $this->copyDir($this->getCachedWorkDirPath($version), $this->getWorkDirPath());
    }

    /**
     * Copy directory recursively.
     *
     * @param string $source The path of source folder
     * @param string $destination The path of destination folder
     * @return void
     */
    public function copyDir($source, $destination): void
    {
        if (!is_dir(dirname($destination))) {
            mkdir(dirname($destination));
        }

        $this->taskRsync()
            ->arg('-l')
            ->recursive()
            ->fromPath($source . '/')
            ->toPath($destination . '/')
            ->excludeVcs()
            ->run();
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
            ->cloneShallow($this->_getConfig('template_repo'), '.', $branch)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Starts docker-sync
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
                    'username' => $this->_getConfig('composer_magento_username'),
                    'password' => $this->_getConfig('composer_magento_password'),
                ]
            ],
        ];

        $githubToken = $this->_getConfig('composer_github_token');
        if ($githubToken) {
            $auth['github-oauth'] = [
                'github.com' => $githubToken,
            ];
        }

        return $this->taskWriteToFile($this->getWorkDirPath() . '/auth.json')
            ->line(json_encode($auth))
            ->run()
            ->wasSuccessful();
    }

    /**
     * Creates ZIP file with tested code
     *
     * @param string $name
     * @param string $version
     * @param array $skippedFiles
     * @return bool
     */
    public function createArtifactCurrentTestedCode(string $name, string $version, array $skippedFiles = []): bool
    {
        $composerPath = codecept_root_dir('composer.json');
        $composerRaw = trim(file_get_contents($composerPath));
        $composerArray = json_decode($composerRaw, true);
        $composerArray['version'] = $version;

        // Set needed version
        $resultTmpVersion = $this->taskWriteToFile($composerPath)
            ->line(json_encode($composerArray))
            ->run()
            ->wasSuccessful();

        $skippedFiles = array_merge(
            ['..', '.', 'vendor', '.git', BaseModule::WORK_DIR, BaseModule::WORK_DIR_CACHE, 'composer.lock'],
            $skippedFiles
        );
        $files = [];

        foreach (array_diff(scandir(codecept_root_dir()), $skippedFiles) as $file) {
            $files[$file] = codecept_root_dir($file);
        }

        // ZIP files
        $resultZip = $this->taskPack($this->getArtifactsDir() . '/' . $name . '.zip')
            ->add($files)
            ->run()
            ->wasSuccessful();

        // Revert original version
        $resultRevert = $this->taskWriteToFile($composerPath)
            ->line($composerRaw)
            ->run()
            ->wasSuccessful();

        return $resultTmpVersion && $resultRevert && $resultZip;
    }

    /**
     * Creates ZIP file with code from codeception data directory
     *
     * @param string $name
     * @param string $path
     * @return bool
     */
    public function createArtifact(string $name, string $path): bool
    {
        $path = trim($path, '/');
        $files = [];
        foreach (array_diff(scandir(codecept_data_dir($path)), ['..', '.']) as $file) {
            $files[$file] = codecept_data_dir($path . '/' . $file);
        }

        // ZIP files
        return $this->taskPack($this->getArtifactsDir() . '/' . $name . '.zip')
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
     * Adds some dependency to require section in composer.json
     *
     * @param string $name
     * @param string $version
     * @return bool
     */
    public function addDependencyToComposer(string $name, string $version): bool
    {
        return $this->retry(
            function () use ($name, $version) {
                return $this->taskComposerRequire('composer')
                    ->dependency($name, $version)
                    ->noInteraction()
                    ->option('--no-update')
                    ->printOutput($this->_getConfig('printOutput'))
                    ->interactive(false)
                    ->dir($this->getWorkDirPath())
                    ->run()
                    ->wasSuccessful();
            }
        );
    }

    /**
     * @param callable $callback
     * @param int $retries
     * @return bool
     */
    private function retry(callable $callback, int $retries = 2): bool
    {
        $result = false;

        for ($i = $retries; $i > 0; $i--) {
            $result = $callback();
            if ($result) {
                return $result;
            }

            sleep(5);
        }

        return $result;
    }

    /**
     * Removes dependency from require section in composer.json
     *
     * @param string $name
     * @return bool
     */
    public function removeDependencyFromComposer(string $name): bool
    {
        return $this->taskComposerRemove('composer')
            ->arg($name)
            ->dir($this->getWorkDirPath())
            ->noInteraction()
            ->option('--no-update')
            ->printOutput($this->_getConfig('printOutput'))
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
        return $this->addGitRepoToComposer('mcd');
    }

    /**
     * Adds cloud-components repo to composer.json
     *
     * @return bool
     */
    public function addCloudComponentsGitRepoToComposer(): bool
    {
        return $this->addGitRepoToComposer('mcc');
    }

    /**
     * Adds cloud-patches repo to composer.json
     *
     * @return bool
     */
    public function addCloudPatchesGitRepoToComposer(): bool
    {
        return $this->addGitRepoToComposer('mcp');
    }

    /**
     * Adds cloud-patches repo to composer.json
     *
     * @return bool
     */
    public function addQualityPatchesGitRepoToComposer(): bool
    {
        return $this->addGitRepoToComposer('mqp');
    }

    /**
     * Adds ece-tools repo to composer.json
     *
     * @return bool
     */
    public function addEceToolsGitRepoToComposer(): bool
    {
        return $this->addGitRepoToComposer('ece_tools');
    }

    /**
     * @param string $name
     * @return bool
     */
    private function addGitRepoToComposer(string $name): bool
    {
        return $this->taskComposerConfig()
            ->set('repositories.' . $name, json_encode(
                [
                    'type' => 'vcs',
                    'url' => $this->_getConfig($name . '_repo')
                ]
            ))->noInteraction()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->run()
            ->wasSuccessful();
    }

    /**
     * Gets dependency version for tested code by name
     *
     * @param string $name
     * @return string
     */
    public function getDependencyVersion(string $name): string
    {
        $composer = json_decode(file_get_contents(codecept_root_dir('composer.json')), true);

        return $composer['require'][$name] ?? '';
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
        $result = $this->taskExecStack()
            ->printOutput($this->_getConfig('printOutput'))
            ->interactive(false)
            ->dir($this->getWorkDirPath())
            ->exec($command)
            ->run();

        static::$output = $result->getMessage();

        return $result->wasSuccessful();
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
     * Generates docker-compose.yaml using ece-docker command
     *
     * @param string $options
     * @return bool
     * @throws \Robo\Exception\TaskException
     */
    public function generateDockerCompose(string $options = ''): bool
    {
        $customRegistry = $this->_getConfig('custom_registry');
        $options .= $customRegistry
            ? ' --custom-registry=' . $customRegistry
            : '';

        return $this->runEceDockerCommand('build:compose ' . $options);
    }

    /**
     * Replace magento images with custom images in docker-compose.yml
     *
     * @return bool
     */
    public function replaceImagesWithCustom(): bool
    {
        if (true === $this->_getConfig('use_custom_images')) {
            $this->debug('Tests use custom Docker images');
            $path = $this->getWorkDirPath() . DIRECTORY_SEPARATOR . 'docker-compose.yml';

            return (bool)file_put_contents(
                $path,
                preg_replace(
                    '/(magento\/magento-cloud-docker-(\w+)):((\d+\.\d+|latest)(-fpm|-cli)?-(\d+\.\d+\.\d+))/i',
                    sprintf(
                        '%s/magento-cloud-docker-$2:$4$5-%s',
                        $this->_getConfig('custom_images_namespace'),
                        $this->_getConfig('version_custom_images') ?: '$6'
                    ),
                    file_get_contents($path)
                )
            );
        }

        $this->debug('Tests use default Docker images');

        return true;
    }

    /**
     * Replace magento images versions with current magento-cloud-docker version
     *
     * @return bool
     */
    public function replaceImagesWithCurrentDockerVersion(): bool
    {
        $composePath = $this->getWorkDirPath() . DIRECTORY_SEPARATOR . 'docker-compose.yml';

        return (bool)file_put_contents(
            $composePath,
            preg_replace(
                '/magento\/magento-cloud-docker-(.*?)(-\d+\.\d+\.\d+)/i',
                'magento/magento-cloud-docker-$1-' . $this->getMcdVersion(),
                file_get_contents($composePath)
            )
        );
    }

    /**
     * Runs composer update
     *
     * @return bool
     */
    public function composerUpdate(): bool
    {
        return $this->retry(
            function () {
                return $this->taskComposerUpdate('composer')
                    ->printOutput($this->_getConfig('printOutput'))
                    ->interactive(false)
                    ->dir($this->getWorkDirPath())
                    ->run()
                    ->wasSuccessful();
            }
        );
    }

    /**
     * Copies file from _data to work directory
     *
     * @param string $source
     * @param string $destination
     * @param bool $overwrite
     * @return bool
     */
    public function copyFileToWorkDir(string $source, string $destination, bool $overwrite = true): bool
    {
        if (strpos($source, '/') !== 0) {
            $source = codecept_data_dir($source);
        }

        return $this->taskFilesystemStack()
            ->copy($source, $this->getWorkDirPath() . DIRECTORY_SEPARATOR . $destination, $overwrite)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Copies directory from _data to work directory
     *
     * @param string $source
     * @param string $destination
     * @param bool $overwrite
     * @return bool
     */
    public function copyDirToWorkDir(string $source, string $destination, bool $overwrite = true): bool
    {
        if (strpos($source, '/') !== 0) {
            $source = codecept_data_dir($source);
        }

        return $this->taskCopyDir([$source => $this->getWorkDirPath() . DIRECTORY_SEPARATOR . $destination])
            ->overwrite($overwrite)
            ->run()
            ->wasSuccessful();
    }

    /**
     * Returns array from .magento.app.yaml
     *
     * @return array
     */
    public function readAppMagentoYaml(): array
    {
        return $this->readYamlConfiguration($this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_APP_YAML);
    }

    /**
     * Saves configuration in the .magento.app.yaml file
     *
     * @param array $data
     * @return bool
     */
    public function writeAppMagentoYaml(array $data): bool
    {
        return $this->writeYamlConfiguration(
            $this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_APP_YAML,
            $data
        );
    }

    /**
     * Returns array from .magento.env.yaml
     *
     * @return array
     */
    public function readEnvMagentoYaml(): array
    {
        return $this->readYamlConfiguration($this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_ENV_YAML);
    }

    /**
     * Saves configuration in the .magento.env.yaml file
     *
     * @param array $data
     * @return bool
     */
    public function writeEnvMagentoYaml(array $data): bool
    {
        return $this->writeYamlConfiguration(
            $this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_ENV_YAML,
            $data
        );
    }

    /**
     * Returns array from .magento/services.yaml
     *
     * @return array
     */
    public function readServicesYaml(): array
    {
        return $this->readYamlConfiguration(
            $this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_SERVICES_YAML
        );
    }

    /**
     * Saves configuration in the .magento/services.yaml file
     *
     * @param array $data
     * @return bool
     */
    public function writeServicesYaml(array $data): bool
    {
        return $this->writeYamlConfiguration(
            $this->getWorkDirPath() . DIRECTORY_SEPARATOR . self::MAGENTO_SERVICES_YAML,
            $data
        );
    }

    /**
     * @param string $path
     * @return array
     */
    private function readYamlConfiguration(string $path): array
    {
        return Yaml::parseFile($path);
    }

    /**
     * @param string $path
     * @param array $data
     * @return bool
     */
    private function writeYamlConfiguration(string $path, array $data): bool
    {
        return $this->taskWriteToFile($path)
            ->line(Yaml::dump($data, 10, 4, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK))
            ->run()
            ->wasSuccessful();
    }

    /**
     * Returns magento-cloud-docker original version
     *
     * @return string
     */
    private function getMcdVersion(): string
    {
        $mcdVersion = Factory::create(new NullIO(), $this->getRootDirPath() . '/composer.json')
            ->getPackage()
            ->getVersion();

        preg_match('/^\d+\.\d+\.\d+/', $mcdVersion, $matches);

        return $matches[0];
    }
}
