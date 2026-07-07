<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * RabbitMQ acceptance tests for PHP 8.1 — template 2.4.5 maps to 4.2 and 4.3.1.
 *
 * @group php81
 */
class RabbitMq81Cest extends RabbitMqCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.5';

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            ['version' => '4.2'],
            ['version' => '4.3.1'],
        ];
    }
}
