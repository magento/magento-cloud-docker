<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php81
 */
class Elasticsearch81Cest extends ElasticsearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.4';

    /**
     * Provides test data for Elasticsearch tests.
     *
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '7.6',
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
