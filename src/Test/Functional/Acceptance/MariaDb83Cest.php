<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.3 line — MariaDB 11.8, 10.11 functional coverage.
 *
 * @group php83
 */
class MariaDb83Cest extends MariaDbCest
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
                'version' => '11.8',
            ],
            [
                'version' => '10.11',
            ],
        ];
    }
}
