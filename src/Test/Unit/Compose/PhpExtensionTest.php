<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CloudDocker\Test\Unit\Compose;

use Composer\Semver\Constraint\ConstraintInterface;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\PhpExtension;
use Magento\CloudDocker\Service\Config;
use PHPUnit\Framework\MockObject\MockObject as Mock;
use PHPUnit\Framework\TestCase;
use Composer\Semver\VersionParser;

/**
 * @inheritdoc
 */
class PhpExtensionTest extends TestCase
{
    /**
     * @var PhpExtension
     */
    private $phpExtension;

    /**
     * @var Config|Mock
     */
    private $configMock;

    /**
     * @var VersionParser|Mock
     */
    private $versionParserMock;

    protected function setUp()
    {
        $this->configMock = $this->createMock(Config::class);
        $this->versionParserMock = $this->createMock(VersionParser::class);

        $this->phpExtension = new PhpExtension(
            $this->configMock,
            $this->versionParserMock
        );
    }

    /**
     * @param string $phpVersion
     * @param string $normalizePhpVersion
     * @param array $enabledPhpExtensions
     * @param array $disabledPhpExtensions
     * @param array $expectedPhpExtensions
     * @throws ConfigurationMismatchException
     * @dataProvider getTestDataProvider
     */
    public function testGet(
        string $phpVersion,
        string $normalizePhpVersion,
        array $enabledPhpExtensions,
        array $disabledPhpExtensions,
        array $expectedPhpExtensions
    ) {
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $this->versionParserMock->expects($this->once())
            ->method('normalize')
            ->with($phpVersion)
            ->willReturn($normalizePhpVersion);
        $this->configMock->expects($this->once())
            ->method('getEnabledPhpExtensions')
            ->willReturn($enabledPhpExtensions);
        $this->configMock->expects($this->once())
            ->method('getDisabledPhpExtensions')
            ->willReturn($disabledPhpExtensions);
        $this->versionParserMock->expects($this->any())
            ->method('parseConstraints')
            ->willReturn($constraintMock);
        $constraintMock->expects($this->any())
            ->method('matches')
            ->willReturn(true);
        $actualPhpExtensions = $this->phpExtension->get($phpVersion);
        sort($actualPhpExtensions);
        sort($expectedPhpExtensions);
        $this->assertEquals($expectedPhpExtensions, $actualPhpExtensions);
    }

    /**
     * @return array
     */
    public function getTestDataProvider(): array
    {
        return [
            [
                'phpVersion' => '7.0',
                'normalizePhpVersion' => '7.0.0.0',
                'enabledPhpExtensions' => [],
                'disabledPhpExtensions' => [],
                'expectedPhpExtensions' => PhpExtension::DEFAULT_PHP_EXTENSIONS
            ],
            [
                'phpVersion' => '7.1',
                'normalizePhpVersion' => '7.1.0.0',
                'enabledPhpExtensions' => array_keys(PhpExtension::BUILTIN_EXTENSIONS),
                'disabledPhpExtensions' => [],
                'expectedPhpExtensions' => PhpExtension::DEFAULT_PHP_EXTENSIONS
            ],
            [
                'phpVersion' => '7.2',
                'normalizePhpVersion' => '7.2.0.0',
                'enabledPhpExtensions' => array_keys(PhpExtension::AVAILABLE_PHP_EXTENSIONS),
                'disabledPhpExtensions' => [],
                'expectedPhpExtensions' => array_keys(PhpExtension::AVAILABLE_PHP_EXTENSIONS),
            ],
            [
                'phpVersion' => '7.2',
                'normalizePhpVersion' => '7.2.0.0',
                'enabledPhpExtensions' => [],
                'disabledPhpExtensions' => PhpExtension::DEFAULT_PHP_EXTENSIONS,
                'expectedPhpExtensions' => []
            ],
            [
                'phpVersion' => '7.0',
                'normalizePhpVersion' => '7.0.0.0',
                'enabledPhpExtensions' => ['redis', 'xsl', 'json', 'blackfire', 'newrelic'],
                'disabledPhpExtensions' => [],
                'expectedPhpExtensions' => array_merge(
                    PhpExtension::DEFAULT_PHP_EXTENSIONS,
                    ['redis', 'xsl']
                )
            ],
            [
                'phpVersion' => '7.1',
                'normalizePhpVersion' => '7.1.0.0',
                'enabledPhpExtensions' => ['redis', 'xsl', 'json', 'blackfire', 'newrelic'],
                'disabledPhpExtensions' => ['pcntl', 'pdo_mysql', 'soap', 'sysvmsg', 'sysvsem', 'sysvshm'],
                'expectedPhpExtensions' => [
                    'bcmath',
                    'bz2',
                    'calendar',
                    'exif',
                    'gd',
                    'gettext',
                    'intl',
                    'mysqli',
                    'sockets',
                    'opcache',
                    'zip',
                    'redis',
                    'xsl'
                ]
            ],
        ];
    }

    /**
     * @expectedException \Magento\CloudDocker\App\ConfigurationMismatchException
     * @expectedExceptionMessage PHP extension mcrypt is not available for PHP version 7.2.
     * PHP extension fakeExt is not supported.
     */
    public function testGetWithException()
    {
        $constraintMock = $this->getMockForAbstractClass(ConstraintInterface::class);
        $this->versionParserMock->expects($this->once())
            ->method('normalize')
            ->with('7.2')
            ->willReturn('7.2.0.0');
        $this->configMock->expects($this->once())
            ->method('getEnabledPhpExtensions')
            ->willReturn(['mcrypt', 'fakeExt']);
        $this->configMock->expects($this->once())
            ->method('getDisabledPhpExtensions')
            ->willReturn(PhpExtension::DEFAULT_PHP_EXTENSIONS);
        $this->versionParserMock->expects($this->any())
            ->method('parseConstraints')
            ->willReturn($constraintMock);
        $constraintMock->expects($this->any())
            ->method('matches')
            ->willReturn(false);
        $this->phpExtension->get('7.2');
    }
}
