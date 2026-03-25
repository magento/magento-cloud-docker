<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * Valkey 9 is supported from Magento 2.4.9; tests both Valkey 8.0 and 9 here only.
 *
 * @group php85
 */
class Valkey85Cest extends ValkeyCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9-beta';

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '8.0',
            ],
            [
                'version' => '9',
            ],
        ];
    }
}
