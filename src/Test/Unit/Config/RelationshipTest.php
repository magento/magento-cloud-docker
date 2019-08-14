<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Service\Config;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class RelationshipTest extends TestCase
{
    /**
     * @var Config|MockObject
     */
    private $configMock;

    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->configMock = $this->createMock(Config::class);

        $this->relationship = new Relationship($this->configMock);
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testGet()
    {
        $this->configMock->expects($this->exactly(4))
            ->method('getServiceVersion')
            ->willReturnMap([
                ['mysql', '10'],
                ['redis', '8'],
                ['elasticsearch', null],
                ['rabbitmq', '10'],
            ]);

        $relationships = $this->relationship->get();

        $this->assertArrayHasKey('database', $relationships);
        $this->assertArrayHasKey('redis', $relationships);
        $this->assertArrayHasKey('rabbitmq', $relationships);
    }

    /**
     * @expectedExceptionMessage Configuration error
     * @expectedException \Magento\CloudDocker\App\ConfigurationMismatchException
     */
    public function testGetWithException()
    {
        $this->configMock->expects($this->any())
            ->method('getServiceVersion')
            ->willThrowException(new ConfigurationMismatchException('Configuration error'));

        $this->relationship->get();
    }
}
