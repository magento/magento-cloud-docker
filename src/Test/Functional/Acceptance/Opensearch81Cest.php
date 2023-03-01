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
 * @group php81
 */
class Opensearch81Cest extends OpensearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.4';

    /**
     * @return array
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '1.1',
                'xms' => '520m',
                'xmx' => '520m',
                'plugins' => ['analysis-nori'],
                'param' => [
                    'key' => 'node.store.allow_mmap',
                    'value' => 'false',
                    'needle' => '"store":{"allow_mmap":"false"}',
                ]
            ],
            [
                'version' => '1.2',
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
