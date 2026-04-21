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
class Opensearch85Cest extends OpensearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9';

    /**
     * Gets the list of Opensearch versions to test.
     *
     * @return array
     */
    protected function getVersions(): array
    {
        return ['3.5'];
    }
}
