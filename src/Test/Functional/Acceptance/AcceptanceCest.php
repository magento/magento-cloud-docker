<?php
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Codeception\Exception\ModuleConfigException;
use Codeception\Exception\ModuleException;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Robo\Exception\TaskException;
use RuntimeException;
use Throwable;

/**
 * General acceptance tests for Magento Cloud Docker.
 */
class AcceptanceCest extends AbstractCest
{
    /**
     * Default production mode test
     *
     * @param CliTester $I
     * @return void
     * @throws TaskException
     * @throws ModuleConfigException
     * @throws ModuleException
     */
    public function testProductionMode(CliTester $I): void
    {
        $I->assertTrue($I->generateDockerCompose('--mode=production'), 'Command build:compose failed');
        $I->replaceImagesWithCustom();
        $I->assertTrue($this->ensureWorkspaceIsSanitized($I), '.git removal failed');
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase failed');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    /**
     * Custom host production mode test
     *
     * @param CliTester $I
     * @return void
     * @throws TaskException
     * @throws ModuleConfigException
     * @throws ModuleException
     */
    public function testCustomHost(CliTester $I): void
    {
        $I->updateBaseUrl('http://magento2.test/');
        $I->assertTrue(
            $I->generateDockerCompose('--mode=production --host=magento2.test'),
            'Command build:compose failed'
        );
        $I->replaceImagesWithCustom();
        $I->assertTrue($this->ensureWorkspaceIsSanitized($I), '.git removal failed');
        $I->startEnvironment();
        $I->assertTrue($I->runDockerComposeCommand('run build cloud-build'), 'Build phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-deploy'), 'Deploy phase failed');
        $I->assertTrue($I->runDockerComposeCommand('run deploy cloud-post-deploy'), 'Post deploy phase failed');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }

    private function getValidatedWorkDirPath(CliTester $I): string
    {
        $workDir = $I->getWorkDirPath();

        if (empty($workDir) || $workDir === DIRECTORY_SEPARATOR) {
            throw new RuntimeException('Invalid working directory');
        }

        return $workDir;
    }

    /**
     * Ensures the .git directory is removed from the work directory,
     * first trying to remove it directly from the host,
     * and if that fails, attempting to remove it from within the Docker container.
     *
     * @param CliTester $I
     * @return bool
     */
    private function ensureWorkspaceIsSanitized(CliTester $I): bool
    {
        try {
            $workDir = $this->getValidatedWorkDirPath($I);
            $gitPath = $workDir . DIRECTORY_SEPARATOR . '.git';

            // Already clean
            if (!is_dir($gitPath)) {
                return true;
            }

            // Step 1: Remove via PHP (host)
            $this->removeDirectory($gitPath);

            if (!is_dir($gitPath)) {
                return true;
            }

            // Step 2: Docker fallback
            $I->comment('Host removal failed, trying Docker fallback...');

            $service = getenv('DOCKER_APP_SERVICE') ?: 'app';

            $removeCommand = sprintf(
                'docker compose exec -T %s bash -c %s',
                escapeshellarg($service),
                escapeshellarg("rm -rf {$gitPath}")
            );

            exec($removeCommand, $output, $code);

            if ($code !== 0) {
                $I->comment('Docker removal failed: ' . implode("\n", $output));
                return false;
            }

            // Step 3: Verify inside container (critical)
            $checkCommand = sprintf(
                'docker compose exec -T %s test ! -d %s',
                escapeshellarg($service),
                escapeshellarg($gitPath)
            );

            // @SuppressWarnings(PHPMD.UnusedLocalVariable)
            exec($checkCommand, $output, $checkCode);

            return $checkCode === 0;
        } catch (Throwable $e) {
            $I->comment('Git removal failed: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Recursively removes a directory and its contents.
     *
     * @param string $dir
     * @return void
     */
    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            $path = $file->getRealPath();

            if ($file->isDir()) {
                if (!@rmdir($path)) {
                    throw new RuntimeException("Failed to remove directory: {$path}");
                }
            } else {
                if (!@unlink($path)) {
                    throw new RuntimeException("Failed to remove file: {$path}");
                }
            }
        }

        if (!@rmdir($dir)) {
            throw new RuntimeException("Failed to remove root directory: {$dir}");
        }
    }

    /**
     * Runs after each test to clean up the test environment,
     * with enhanced error handling to ensure cleanup proceeds even if issues arise.
     * The method attempts to stop the Docker environment if a compose file is present,
     * and always tries to remove the work directory, logging any issues encountered during the process.
     *
     * @param CliTester $I Codeception CLI tester instance.
     * @return void
     */
    public function _after(CliTester $I): void
    {
        $workDir = null;

        try {
            $workDir     = $this->getValidatedWorkDirPath($I);
            $composeFile = $workDir . DIRECTORY_SEPARATOR . 'docker-compose.yml';

            if (is_file($composeFile) && filesize($composeFile) > 0) {
                try {
                    $I->runDockerComposeCommand('ps');
                    $I->stopEnvironment();
                } catch (Throwable $e) {
                    $I->comment('Docker cleanup failed: ' . $e->getMessage());
                }
            } else {
                $I->comment('docker-compose.yml not found, skipping docker cleanup');
            }
        } catch (Throwable $e) {
            $I->comment('Cleanup initialization failed: ' . $e->getMessage());
        } finally {
            try {
                $I->removeWorkDir();
            } catch (Throwable $e) {
                $I->comment('Workdir cleanup failed: ' . $e->getMessage());
            }
        }
    }
}
