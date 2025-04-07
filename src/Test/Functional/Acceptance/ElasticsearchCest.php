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
 * @group php83
 */
class ElasticsearchCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @return void
     * @throws TaskException
     */
    public function testElasticsearch(CliTester $I, Example $data)
    {
        $I->generateDockerCompose($this->buildCommand($data));
        $I->replaceImagesWithCustom();
        $I->startEnvironment();
        if (!empty($data['plugins'])) {
            $I->runDockerComposeCommand('logs elasticsearch');
            foreach ($data['plugins'] as $plugin) {
                $I->seeInOutput($plugin);
            }
        }
        $I->runDockerComposeCommand('exec -T elasticsearch ps aux | grep elasticsearch');
        $I->seeInOutput('-Xms' . $data['xms']);
        $I->seeInOutput('-Xmx' . $data['xmx']);

        if (!empty($data['param'])) {
            $I->runDockerComposeCommand('exec -T elasticsearch curl http://localhost:9200/_nodes/settings');
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
            '--mode=production --es=%s --es-env-var="ES_JAVA_OPTS=-Xms%s -Xmx%s" --no-os',
            $data['version'],
            $data['xms'],
            $data['xmx']
        );

        if (!empty($data['param'])) {
            $command .= " --es-env-var={$data['param']['key']}={$data['param']['value']}";
        }
        if (!empty($data['plugins'])) {
            $command .= sprintf(' --es-env-var="ES_PLUGINS=%s"', implode(' ', $data['plugins']));
        }

        return $command;
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '7.10',
                'xms' => '520m',
                'xmx' => '520m',
                'plugins' => ['analysis-nori'],
                'param' => [
                    'key' => 'node.store.allow_mmap',
                    'value' => 'false',
                    'needle' => '"store":{"allow_mmap":"false"}',
                ]
            ],
        ];
    }
}
