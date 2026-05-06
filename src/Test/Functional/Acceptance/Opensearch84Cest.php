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
class Opensearch84Cest extends OpensearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.8';

    /**
     * Gets the list of Opensearch versions to test.
     *
     * @return array
     */
    protected function getVersions(): array
    {
        return ['2.3', '2.4', '2.5', '2.12', '2.19', '3.0'];
    }
}
