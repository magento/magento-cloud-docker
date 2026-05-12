<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.5 line — MariaDB 11.4, 11.8, 12.2, 12.3-rc functional coverage.
 *
 * @group php85
 */
class MariaDb85Cest extends MariaDbCest
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
                'version' => '11.4',
            ],
            [
                'version' => '11.8',
            ],
            [
                'version' => '12.2',
            ],
            [
                'version' => '12.3-rc',
                'version_assert' => '12.3',
            ],
        ];
    }
}
