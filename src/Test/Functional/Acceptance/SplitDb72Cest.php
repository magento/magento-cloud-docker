<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php72
 */
class SplitDb72Cest extends SplitDbCest
{
    protected const TEMPLATE_VERSION = '2.3.2';
}
