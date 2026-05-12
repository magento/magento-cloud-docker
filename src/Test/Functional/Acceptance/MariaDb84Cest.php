<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.4 line — MariaDB 11.8, 11.4 functional coverage.
 *
 * @group php84
 */
class MariaDb84Cest extends MariaDbCest
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
                'version' => '11.8',
            ],
            [
                'version' => '11.4',
            ],
        ];
    }
}
