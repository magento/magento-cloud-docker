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
 * @group php73
 */
class ElasticsearchCest extends AbstractCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.3.3';

    /**
     * @param CliTester $I
     * @param Example $data
     * @dataProvider dataProvider
     * @return void
     * @throws TaskException
     */
    public function testElasticsearch(CliTester $I, Example $data)
    {
        $command = sprintf(
            'build:compose --mode=production --es=%s --es-env-var="ES_JAVA_OPTS=-Xms%s -Xmx%s"',
            $data['version'],
            $data['xms'],
            $data['xmx']
        );

        if (!empty($data['param'])) {
            $command .= " --es-env-var={$data['param']['key']}={$data['param']['value']}";
        }
        $I->runEceDockerCommand($command);
        $I->replaceImagesWithGenerated();
        $I->startEnvironment();
        $I->runDockerComposeCommand('exec -T elasticsearch ps aux | grep elasticsearch');
        $I->seeInOutput('-Xms' . $data['xms']);
        $I->seeInOutput('-Xmx' . $data['xmx']);

        if (!empty($data['param'])) {
            $I->runDockerComposeCommand('exec -T elasticsearch curl http://localhost:9200/_nodes/settings');
            $I->seeInOutput($data['param']['needle']);
        }
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '6.5',
                'xms' => '518m',
                'xmx' => '518m',
                'param' => [
                    'key' => 'node.store.allow_mmapfs',
                    'value' => 'false',
                    'needle' => '"store":{"allow_mmapfs":"false"}',
                ]
            ],
            [
                'version' => '7.5',
                'xms' => '520m',
                'xmx' => '520m',
                'param' => [
                    'key' => 'node.store.allow_mmap',
                    'value' => 'false',
                    'needle' => '"store":{"allow_mmap":"false"}',
                ]
            ],
        ];
    }
}
