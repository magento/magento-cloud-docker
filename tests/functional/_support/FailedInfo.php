<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Codeception\Extension;

use Codeception\Events;
use Codeception\Extension;
use Codeception\Event\FailEvent;

/**
 * Custom extension to output troubleshooting information for failed functional tests.
 */
class FailedInfo extends Extension
{
    /**
     * Test failed event handler
     */
    public static $events = [
        Events::TEST_FAIL => 'testFailed'
    ];

    /**
     * Method to handle failed tests.
     *
     * @param FailEvent $e
     * @return void
     */
    public function testFailed(FailEvent $e): void {
        $failure = $e->getFail();
        $this->writeln('------------------------------------');
        $this->writeln('Message: ' . $failure->getMessage());
        $this->writeln('------------------------------------');
        $this->writeln('Stack Trace: ');
        $this->writeln($failure->getTraceAsString());
        $this->writeln('------------------------------------');
    }
}