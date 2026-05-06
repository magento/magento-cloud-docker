<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * RabbitMQ acceptance tests for PHP 8.5 — template 2.4.9 maps to 4.1 || 4.2 (>= 2.4.9-beta1 line).
 *
 * @group php85
 */
class RabbitMq85Cest extends RabbitMqCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9';

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
