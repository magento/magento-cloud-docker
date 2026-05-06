<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * RabbitMQ acceptance tests for PHP 8.3 — template 2.4.7 maps to ~3.12 || ~3.13.
 *
 * @group php83
 */
class RabbitMq83Cest extends RabbitMqCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '3.12',
            ],
            [
                'version' => '3.12-management',
            ],
            [
                'version' => '3.13',
            ],
            [
                'version' => '3.13-management',
            ],
        ];
    }
}
