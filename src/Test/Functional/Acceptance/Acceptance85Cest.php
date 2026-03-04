<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * @group php85
 */
class Acceptance85Cest extends AcceptanceCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9-alpha-opensearch3.0';
}
