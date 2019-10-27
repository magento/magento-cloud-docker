<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Service;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\ServiceFactory;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * @inheritdoc
 */
class ServiceFactoryTest extends TestCase
{
    /**
     * @var ServiceFactory
     */
    private $factory;

    /**
     * @var FileList|MockObject
     */
    private $fileListMock;

    /**
     * @inheritdoc
     */
    protected function setUp(): void
    {
        $this->fileListMock = $this->createMock(FileList::class);

        $this->fileListMock->method('getComposer')
            ->willReturn(__DIR__ . '/../../../../composer.json');

        $this->factory = new ServiceFactory(
            $this->fileListMock
        );
    }

    /**
     * @throws ConfigurationMismatchException
     */
    public function testCreate()
    {
        $this->factory->create(ServiceFactory::SERVICE_CLI, '7.0');
    }

    public function testCreateServiceNotSupported()
    {
        $this->expectException(ConfigurationMismatchException::class);
        $this->expectExceptionMessage('Service "test" is not supported');

        $this->factory->create('test', '5.6');
    }
}
