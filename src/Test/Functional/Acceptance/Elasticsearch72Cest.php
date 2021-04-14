<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

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
