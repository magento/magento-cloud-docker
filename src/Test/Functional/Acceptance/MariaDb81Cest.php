<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.1 line — MariaDB 10.11, 10.6 functional coverage.
 *
 * @group php81
 */
class MariaDb81Cest extends MariaDbCest
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
            [
                'version' => '10.11',
            ],
            [
                'version' => '10.6',
            ],
        ];
    }
}
