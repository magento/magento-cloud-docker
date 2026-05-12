<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * RabbitMQ acceptance tests for PHP 8.2 — template 2.4.6 maps to ~3.9 || ~3.11.
 *
 * @group php82
 */
class RabbitMq82Cest extends RabbitMqCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.6';

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '3.9',
            ],
            [
                'version' => '3.9-management',
            ],
            [
                'version' => '3.11',
            ],
            [
                'version' => '3.11-management',
            ],
        ];
    }
}
