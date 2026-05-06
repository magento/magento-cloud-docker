<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.5 / Adobe Commerce 2.4.9 line — Valkey 9 (per Adobe Commerce Valkey/Redis support matrix).
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
                'version' => '9',
            ],
        ];
    }
}
