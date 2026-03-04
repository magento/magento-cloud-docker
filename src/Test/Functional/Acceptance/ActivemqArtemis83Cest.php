<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * ActiveMQ Artemis acceptance tests for PHP 8.3 and Magento 2.4.x
 *
 * @group php83
 */
class ActivemqArtemis83Cest extends ActivemqArtemisCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.8';
}
