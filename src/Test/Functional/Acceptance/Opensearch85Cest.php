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
 * @group php85
 */
class Opensearch85Cest extends OpensearchCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9';

    /**
     * Opensearch versions tested for each additional template version
     */
    private const ADDITIONAL_TEMPLATE_OPENSEARCH_VERSIONS = [
        '2.4.10-alpha' => ['3.7'],
    ];

    /**
     * Gets the list of Opensearch versions to test.
     *
     * @return array
     */
    protected function getVersions(): array
    {
        return ['3.5','3.7'];
    }

    /**
     * Declared here so that the data provider of this class is used for the test.
     *
     * @param        CliTester $I
     * @param        Example   $data
     * @dataProvider dataProvider
     * @return       void
     * @throws       TaskException
     */
    public function testOpensearch(CliTester $I, Example $data): void
    {
        parent::testOpensearch($I, $data);
    }

    /**
     * @inheritDoc
     */
    protected function dataProvider(): array
    {
        $datasets = parent::dataProvider();
        $prototype = $datasets[0];

        foreach (self::ADDITIONAL_TEMPLATE_OPENSEARCH_VERSIONS as $templateVersion => $opensearchVersions) {
            foreach ($opensearchVersions as $opensearchVersion) {
                $datasets[] = array_merge(
                    $prototype,
                    ['version' => $opensearchVersion, 'template_version' => $templateVersion]
                );
            }
        }

        return $datasets;
    }
}
