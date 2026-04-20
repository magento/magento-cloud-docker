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
 * Generic Opensearch tests to validate connectivity and
 * basic functionality within the Magento Cloud Docker environment.
 */
class OpensearchCest extends AbstractCest
{
    /**
     * Gets the list of Opensearch versions to test.
     * Can be overridden by child classes.
     *
     * @return array
     */
    protected function getVersions(): array
    {
        return ['1.1', '1.2', '1.3', '2.3', '2.4', '2.5', '2.12', '2.19', '3.0', '3.5'];
    }

    /**
     * Tests Opensearch functionality and connectivity within
     * the Magento Cloud Docker environment.
     *
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @return void
     * @throws TaskException
     */
    public function testOpensearch(CliTester $I, Example $data): void
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        if (!empty($data['plugins'])) {
            $I->runDockerComposeCommand('logs opensearch');
            foreach ($data['plugins'] as $plugin) {
                $I->seeInOutput($plugin);
            }
        }
        $I->runDockerComposeCommand('exec -T opensearch curl localhost:9200/_nodes');
        $I->seeInOutput('-Xms' . $data['xms']);
        $I->seeInOutput('-Xmx' . $data['xmx']);

        if (!empty($data['param'])) {
            $I->runDockerComposeCommand('exec -T opensearch curl http://localhost:9200/_nodes/settings');
            $I->seeInOutput($data['param']['needle']);
        }
    }

    /**
     * Builds build:compose command from given test data
     *
     * @param Example $data
     * @return string
     */
    private function buildCommand(Example $data): string
    {
        $command = sprintf(
            '--mode=production --os=%s --os-env-var="OPENSEARCH_JAVA_OPTS=-Xms%s -Xmx%s"',
            $data['version'],
            $data['xms'],
            $data['xmx']
        );

        if (!empty($data['param'])) {
            $command .= " --os-env-var={$data['param']['key']}={$data['param']['value']}";
        }
        if (!empty($data['plugins'])) {
            $command .= sprintf(' --os-env-var="OS_PLUGINS=%s"', implode(' ', $data['plugins']));
        }

        return $command;
    }

    /**
     * Provides test data for Opensearch tests.
     *
     * @return array
     */
    protected function dataProvider(): array
    {
        return array_map(
            fn(string $version) => $this->buildTestData($version),
            $this->getVersions()
        );
    }

    /**
     * Builds a test data entry for a given Opensearch version.
     *
     * @param string $version
     * @return array
     */
    private function buildTestData(string $version): array
    {
        return [
            'version' => $version,
            'xms'     => '520m',
            'xmx'     => '520m',
            'plugins' => ['analysis-nori'],
            'param'   => [
                'key'    => 'node.store.allow_mmap',
                'value'  => 'false',
                'needle' => '"store":{"allow_mmap":"false"}',
            ]
        ];
    }
}
