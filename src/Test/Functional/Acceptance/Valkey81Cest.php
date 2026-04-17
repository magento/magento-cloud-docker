<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * PHP 8.1 / Adobe Commerce 2.4.5 line — Valkey 8.1 (e.g. 2.4.5-p17 and later per release notes).
 *
 * @group php81
 */
class Valkey81Cest extends ValkeyCest
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
                'version' => '8.1',
            ],
        ];
    }
}
