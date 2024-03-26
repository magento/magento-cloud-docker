<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;

/**
 * @group php82
 */
class Developer82Cest extends DeveloperCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.6';
}
