<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * ActiveMQ Artemis acceptance tests for PHP 8.2 and Magento 2.4.x
 *
 * @group php82
 */
class ActivemqArtemis82Cest extends ActivemqArtemisCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.7';
}
