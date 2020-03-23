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
class ElasticsearchCest extends AbstractAcceptanceCest
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
        $xms = '512m';
        $xmx = '512m';
        $param = 'allow_mmap';
        $versionsForCheckingParams = ['6.5', '7.5'];
        $command = sprintf(
            'build:compose --mode=production --es=%s --es-env-var=ES_JAVA_OPTS="-Xms%s -Xmx%s"',
            $data['version'],
            $xms,
            $xmx
        );
        if (in_array($data['version'], $versionsForCheckingParams)) {
            if ('6.5' === $data['version']) {
                $param .= 'fs';
            }
            $command .= " --es-env-var=node.store.$param=false";
        }
        $I->runEceDockerCommand($command);
        $I->startEnvironment();
        $I->runDockerComposeCommand('exec -T elasticsearch ps aux | grep elasticsearch');
        $I->seeInOutput('-Xms' . $xms);
        $I->seeInOutput('-Xmx' . $xmx);

        if (in_array($data['version'], $versionsForCheckingParams)) {
            $I->runDockerComposeCommand('exec -T elasticsearch curl http://localhost:9200/_nodes/settings');
            $I->seeInOutput(sprintf('"store":{"%s":"false"}', $param));
        }
    }

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            ['version' => '1.7'],
            ['version' => '2.4'],
            ['version' => '5.2'],
            ['version' => '6.5'],
            ['version' => '7.5'],
        ];
    }
}
