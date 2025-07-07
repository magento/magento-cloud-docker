<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php84
 */
class Valkey84Cest extends ValkeyCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.8';
}
