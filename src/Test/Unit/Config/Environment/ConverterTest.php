<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Unit\Config\Environment;

use Magento\CloudDocker\Config\Environment\Converter;
use PHPUnit\Framework\TestCase;

/**
 * @inheritDoc
 */
class ConverterTest extends TestCase
{
    /**
     * @var Converter
     */
    private $converter;

    /**
     * @inheritDoc
     */
    protected function setUp(): void
    {
        $this->converter = new Converter();
    }

    public function testConvert()
    {
        $this->assertSame(
            [
                'MAGENTO_RUN_MODE=production',
                'DEBUG=false',
                'PHP_ENABLE_XDEBUG=true',
            ],
            $this->converter->convert([
                'MAGENTO_RUN_MODE' => 'production',
                'DEBUG' => false,
                'PHP_ENABLE_XDEBUG' => true,
            ])
        );
    }
}
