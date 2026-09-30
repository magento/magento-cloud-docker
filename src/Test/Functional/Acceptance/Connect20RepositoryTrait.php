<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Symfony\Component\Yaml\Yaml;

/**
 * Wires the connect20 composer repository into the work dir so pre-release (alpha) builds can be resolved.
 */
trait Connect20RepositoryTrait
{
    /**
     * Adds connect20 credentials to auth.json and the connect20 repository to composer.json.
     *
     * @param CliTester $I
     * @return void
     */
    protected function applyConnect20Configuration(CliTester $I): void
    {
        $this->ensureConnect20Auth($I);
        $this->configureConnect20Repository($I);
    }

    /**
     * @return string
     */
    protected function getConnect20Host(): string
    {
        return 'connect20-qa01.magedevteam.com';
    }

    /**
     * Prioritize connect20 repository and make repo.magento.com non-canonical,
     * so alpha builds can be resolved from connect20.
     *
     * @param CliTester $I
     * @return void
     */
    private function configureConnect20Repository(CliTester $I): void
    {
        $workDir = $I->getWorkDirPath();
        $authPath = $workDir . '/auth.json';
        $composerPath = $workDir . '/composer.json';

        if (!is_file($authPath) || !is_file($composerPath)) {
            return;
        }

        $auth = json_decode((string)file_get_contents($authPath), true);
        $composer = json_decode((string)file_get_contents($composerPath), true);

        if (!is_array($auth) || !is_array($composer)) {
            return;
        }

        $connect20Host = $this->resolveConnect20Host($auth) ?? $this->getConnect20Host();

        $connect20Repository = [
            'type' => 'composer',
            'url' => 'https://' . $connect20Host . '/',
            'canonical' => false,
        ];

        $repositories = $composer['repositories'] ?? [];
        if (!is_array($repositories)) {
            $repositories = [];
        }

        $filteredRepositories = [];
        $repoMagentoIndex = null;

        foreach ($repositories as $repository) {
            if (!is_array($repository)) {
                continue;
            }

            if (($repository['url'] ?? '') === $connect20Repository['url']) {
                continue;
            }

            if (($repository['url'] ?? '') === 'https://repo.magento.com/') {
                $repository['canonical'] = false;
                $repoMagentoIndex = count($filteredRepositories);
            }

            $filteredRepositories[] = $repository;
        }

        // Keep connect20 ahead of repo.magento.com, but non-canonical, so repo fallbacks remain available.
        if ($repoMagentoIndex === null) {
            array_unshift($filteredRepositories, $connect20Repository);
        } else {
            array_splice($filteredRepositories, $repoMagentoIndex, 0, [$connect20Repository]);
        }

        $composer['repositories'] = $filteredRepositories;
        $composer['minimum-stability'] = 'alpha';

        file_put_contents(
            $composerPath,
            (string)json_encode($composer, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    /**
     * @param array $auth
     * @return string|null
     */
    private function resolveConnect20Host(array $auth): ?string
    {
        $hosts = array_keys($auth['http-basic'] ?? []);
        foreach ($hosts as $host) {
            if (!is_string($host) || $host === 'repo.magento.com') {
                continue;
            }

            if (stripos($host, 'connect20') !== false || stripos($host, 'qa01') !== false) {
                return $host;
            }
        }

        return null;
    }

    /**
     * Ensures auth.json contains connect20 host credentials.
     *
     * @param CliTester $I
     * @return void
     */
    private function ensureConnect20Auth(CliTester $I): void
    {
        $authPath = $I->getWorkDirPath() . '/auth.json';
        if (!is_file($authPath)) {
            return;
        }

        $auth = json_decode((string)file_get_contents($authPath), true);
        if (!is_array($auth)) {
            return;
        }

        $httpBasic = $auth['http-basic'] ?? [];
        if (!is_array($httpBasic)) {
            $httpBasic = [];
        }

        $qaCreds = $this->resolveQaComposerCredentials();
        if ($qaCreds !== null) {
            $httpBasic[$this->getConnect20Host()] = $qaCreds;
        } elseif (!isset($httpBasic[$this->getConnect20Host()])) {
            $repoCreds = $httpBasic['repo.magento.com'] ?? null;
            if (!is_array($repoCreds)) {
                return;
            }

            $httpBasic[$this->getConnect20Host()] = $repoCreds;
        }

        $auth['http-basic'] = $httpBasic;
        file_put_contents(
            $authPath,
            (string)json_encode($auth, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . PHP_EOL
        );
    }

    /**
     * Resolve dedicated QA composer credentials from local functional config.
     *
     * @return array<string, string>|null
     */
    private function resolveQaComposerCredentials(): ?array
    {
        $codeceptionConfigPath = dirname(__DIR__, 4) . '/codeception.dist.yml';
        if (!is_file($codeceptionConfigPath)) {
            return null;
        }

        $codeceptionConfig = Yaml::parseFile($codeceptionConfigPath);
        if (!is_array($codeceptionConfig)) {
            return null;
        }

        $moduleConfig = $codeceptionConfig['modules']['config'][
            'Magento\\CloudDocker\\Test\\Functional\\Codeception\\TestInfrastructure'
        ] ?? null;

        if (!is_array($moduleConfig)) {
            return null;
        }

        $username = $moduleConfig['composer_qa_username'] ?? null;
        $password = $moduleConfig['composer_qa_password'] ?? null;

        if (!is_string($username) || !is_string($password) || $username === '' || $password === '') {
            return null;
        }

        if (
            ($username[0] === '%' && substr($username, -1) === '%')
            || ($password[0] === '%' && substr($password, -1) === '%')
        ) {
            return null;
        }

        return [
            'username' => $username,
            'password' => $password,
        ];
    }
}
