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
 * @group php72
 */
class Elasticsearch72Cest extends ElasticsearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.3.0';

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '1.7',
                'xms' => '512m',
                'xmx' => '512m',
            ],
            [
                'version' => '2.4',
                'xms' => '514m',
                'xmx' => '514m',
            ],
            [
                'version' => '5.2',
                'xms' => '516m',
                'xmx' => '516m',
                'param' => [
                    'key' => 'index.store.type',
                    'value' => 'fs',
                    'needle' => '"index":{"store":{"type":"fs"}}',
                ]
            ],
        ];
    }
}
