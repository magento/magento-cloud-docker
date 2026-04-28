<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.2 line — MariaDB 10.11 functional coverage.
 *
 * @group php82
 */
class MariaDb82Cest extends MariaDbCest
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
                'version' => '10.11',
            ],
        ];
    }
}
