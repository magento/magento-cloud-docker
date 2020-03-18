<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php71
 */
class SplitDb71Cest extends SplitDbCest
{
    protected const TEMPLATE_VERSION = '2.2.10';
}
