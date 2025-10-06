<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Functional\Acceptance;

/**
 * ActiveMQ Artemis acceptance tests for PHP 8.4 and Magento 2.4.x
 * 
 * @group php84
 */
class ActivemqArtemis84Cest extends ActivemqArtemisCest
{
    /**
     * Template version for testing
     */
    protected const TEMPLATE_VERSION = '2.4.9-alpha-opensearch3.0';

    /**
     * @inheritdoc
     */
    protected function basicFunctionalityDataProvider(): array
    {
        return [
            'artemis-2.42.0' => [
                'version' => '2.42.0',
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function customConfigurationDataProvider(): array
    {
        return [
            'custom-artemis-config' => [
                'servicesConfig' => [
                    'activemq' => [
                        'type' => 'activemq-artemis:2.42.0',
                        'disk' => 2048,
                    ]
                ],
                'expectedEnvVars' => [
                    'ARTEMIS_USER' => 'admin',
                    'ARTEMIS_PASSWORD' => 'admin',
                ],
            ],
            'custom-memory-config' => [
                'servicesConfig' => [
                    'activemq' => [
                        'type' => 'activemq-artemis:2.42.0',
                        'disk' => 4096,
                    ]
                ],
                'expectedEnvVars' => [
                    'ARTEMIS_USER' => 'admin',
                    'ARTEMIS_PASSWORD' => 'admin',
                ],
            ],
        ];
    }

    /**
     * @inheritdoc
     */
    protected function errorScenariosDataProvider(): array
    {
        return [
            'invalid-version' => [
                'servicesConfig' => [
                    'activemq' => [
                        'type' => 'activemq-artemis:invalid.version',
                    ]
                ],
                'expectGenerationFailure' => false, // Docker compose generation should succeed
                'expectStartFailure' => true, // But container start should fail
                'expectedErrorMessage' => null,
            ],
            'missing-type' => [
                'servicesConfig' => [
                    'activemq' => [
                        'disk' => 2048,
                    ]
                ],
                'expectGenerationFailure' => true, // Should fail during generation
                'expectStartFailure' => false,
                'expectedErrorMessage' => 'type',
            ],
        ];
    }
}
