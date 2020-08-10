<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

use Magento\CloudDocker\Test\Functional\Codeception\MagentoDb;
use Robo\Exception\TaskException;

/**
 * @group php74
 */
class SplitDbCest extends AbstractCest
{
    private const SPLIT_TYPES = ['quote', 'sales'];

    private const QUOTE_TABLES = [
        'quote_id_mask',
        'quote_address_item',
        'quote_address',
        'quote',
    ];

    private const SALES_TABLES = [
        'sales_invoice',
        'sales_invoice_grid',
        'sales_invoice_item',
        'sales_order',
        'sales_order_grid',
        'sales_order_tax',
    ];

    /**
     * @param \CliTester $I
     * @throws TaskException
     */
    public function testSplitDbOnProductionMode(\CliTester $I): void
    {
        $services = $I->readServicesYaml();
        $magentoApp = $I->readAppMagentoYaml();

        foreach (self::SPLIT_TYPES as $service) {
            $services['mysql-' . $service]['type'] = 'mysql:10.2';
            $magentoApp['relationships']['database-' . $service] = 'mysql-' . $service . ':mysql';
        }

        $I->writeServicesYaml($services);
        $I->writeAppMagentoYaml($magentoApp);
        $I->writeEnvMagentoYaml([
            'stage' => [
                'global' => ['SCD_ON_DEMAND' => true],
                'deploy' => ['SPLIT_DB' => self::SPLIT_TYPES]
            ]
        ]);

        $I->runEceDockerCommand(sprintf(
            'build:compose --mode=production --expose-db-port=%s --expose-db-quote-port=%s --expose-db-sales-port=%s',
            $I->getExposedPort(),
            $I->getExposedPort(MagentoDb::KEY_DB_QUOTE),
            $I->getExposedPort(MagentoDb::KEY_DB_SALES)
        ));
        $I->replaceImagesWithGenerated();
        $I->startEnvironment();
        $I->runDockerComposeCommand('run build cloud-build');
        $I->runDockerComposeCommand('run deploy cloud-deploy');

        $I->amConnectedToDatabase(MagentoDb::KEY_DB_QUOTE);
        foreach (self::QUOTE_TABLES as $quoteTable) {
            $I->grabNumRecords($quoteTable);
        }

        $I->amConnectedToDatabase(MagentoDb::KEY_DB_SALES);
        foreach (self::SALES_TABLES as $salesTable) {
            $I->grabNumRecords($salesTable);
        }
        $I->runDockerComposeCommand('run deploy cloud-post-deploy');
        $I->amOnPage('/');
        $I->see('Home page');
        $I->see('CMS homepage content goes here.');
    }
}
