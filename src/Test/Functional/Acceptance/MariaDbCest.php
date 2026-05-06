<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Robo\Exception\TaskException;

/**
 * MariaDB acceptance tests: compose uses the official MariaDB image, the db
 * service becomes healthy, and the server identifies as MariaDB at the
 * expected major.minor version.
 *
 * Each PHP line Cest (MariaDb81Cest–MariaDb85Cest) must implement {@see dataProvider()}.
 *
 * Data provider rows may include:
 * - `version` (required): image tag passed to `build:compose --db=…`
 * - `version_assert` (optional): substring that must appear in `SELECT VERSION()`; use when
 *   the Hub tag (e.g. 12.3-rc) is not present in the server version string (e.g. 12.3.1-MariaDB-…)
 */
abstract class MariaDbCest extends AbstractCest
{
    /**
     * Builds compose with an explicit MariaDB image tag, starts the stack, and
     * validates health, TCP reachability from FPM, and VERSION() output.
     *
     * @param CliTester $I
     * @param Example   $data
     *
     * @dataProvider dataProvider
     *
     * @return void
     * @throws TaskException
     */
    public function testMariaDb(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();

        $I->runDockerComposeCommand('ps');
        $I->seeInOutput('db');
        $I->seeInOutput('(healthy)');

        // Assert TCP from FPM to db:3306 on the compose network (bash /dev/tcp, timeout);
        $I->runDockerComposeCommand('exec -T fpm timeout 5 bash -c "</dev/tcp/db/3306"');
        $I->seeInOutput('');

        // Official MariaDB images expose the `mariadb` client; VERSION() may omit pre-release tag text.
        $versionNeedle = $data['version_assert'] ?? $data['version'];
        $I->runDockerComposeCommand(
            'exec -T db mariadb -umagento2 -pmagento2 -e "SELECT VERSION();"'
        );
        $I->seeInOutput('MariaDB');
        $I->seeInOutput($versionNeedle);
    }

    /**
     * Build the `build:compose` argument string for the requested MariaDB version.
     *
     * Disables ES/OS/Redis to keep the stack light; `--db-image=mariadb` pins the upstream image family.
     *
     * @param Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
        return sprintf(
            '--mode=production --db=%s --db-image=mariadb --no-es --no-os --no-redis',
            $data['version']
        );
    }

    /**
     * Provide MariaDB versions for the current PHP / Adobe Commerce test line.
     *
     * @return array<int, array{version: string, version_assert?: string}>
     */
    abstract protected function dataProvider(): array;
}
