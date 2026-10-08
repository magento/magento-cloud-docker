<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

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
     * ActiveMQ Artemis versions tested for each Magento template version
     */
    private const ARTEMIS_VERSIONS_BY_TEMPLATE = [
        self::TEMPLATE_VERSION => ['2.51.0', '2.42.0'],
        '2.4.10-alpha'         => ['2.51.0', '2.54.0'],
    ];

    /**
     * Declared here so that the data provider of this class is used for the test.
     *
     * @param        \CliTester           $I
     * @param        \Codeception\Example $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       \Robo\Exception\TaskException
     */
    public function testActivemqArtemis(\CliTester $I, \Codeception\Example $data): void
    {
        parent::testActivemqArtemis($I, $data);
    }

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        $datasets = [];

        foreach (self::ARTEMIS_VERSIONS_BY_TEMPLATE as $templateVersion => $artemisVersions) {
            foreach ($artemisVersions as $artemisVersion) {
                $dataset = ['version' => $artemisVersion];
                if ($templateVersion !== self::TEMPLATE_VERSION) {
                    $dataset['template_version'] = $templateVersion;
                }
                $datasets[] = $dataset;
            }
        }

        return $datasets;
    }
}
