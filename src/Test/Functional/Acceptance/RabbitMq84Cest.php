<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * RabbitMQ acceptance tests for PHP 8.4 — template 2.4.8 maps to greater than 4.0 and 4.2 or lower.
 *
 * @group php84
 */
class RabbitMq84Cest extends RabbitMqCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.8';

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '4.1',
            ],
            [
                'version' => '4.1-management',
            ],
            [
                'version' => '4.2',
            ],
            [
                'version' => '4.2-management',
            ],
        ];
    }
}
