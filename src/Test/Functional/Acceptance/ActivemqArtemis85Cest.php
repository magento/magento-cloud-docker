<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use CliTester;
use Codeception\Example;
use Robo\Exception\TaskException;

/**
 * ActiveMQ Artemis acceptance tests for PHP 8.5 and Magento 2.4.x
 *
 * @group php85
 */
class ActivemqArtemis85Cest extends ActivemqArtemisCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9';

    /**
     * Declared here so that the data provider of this class is used for the test.
     *
     * @param        CliTester $I
     * @param        Example   $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       TaskException
     */
    public function testActivemqArtemis(CliTester $I, Example $data): void
    {
        parent::testActivemqArtemis($I, $data);
    }

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        return [
            [
                'version' => '2.51.0',
            ],
            [
                'version' => '2.42.0',
            ],
            [
                'version' => '2.54.0',
                'template_version' => '2.4.10-alpha',
            ],
        ];
    }
}
