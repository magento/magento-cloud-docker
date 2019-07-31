<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Composer\Semver\Constraint\Constraint;
use Composer\Semver\VersionParser;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\Config;

/**
 * Returns list of PHP extensions which will be enabled in Docker PHP container.
 */
class PhpExtension
{
    /**
     * Extensions which should be installed by default
     */
    const DEFAULT_PHP_EXTENSIONS = [
        'bcmath',
        'bz2',
        'calendar',
        'exif',
        'gd',
        'gettext',
        'intl',
        'mysqli',
        'pcntl',
        'pdo_mysql',
        'soap',
        'sockets',
        'sysvmsg',
        'sysvsem',
        'sysvshm',
        'opcache',
        'zip',
    ];

    /**
     * Extensions which can be installed or uninstalled
     */
    const AVAILABLE_PHP_EXTENSIONS = [
        'bcmath' => '>=7.0.0 <7.3.0',
        'bz2' => '>=7.0.0 <7.3.0',
        'calendar' => '>=7.0.0 <7.3.0',
        'exif' => '>=7.0.0 <7.3.0',
        'gd' => '>=7.0.0 <7.3.0',
        'geoip' => '>=7.0.0 <7.3.0',
        'gettext' => '>=7.0.0 <7.3.0',
        'gmp' => '>=7.0.0 <7.3.0',
        'igbinary' => '>=7.0.0 <7.3.0',
        'imagick' => '>=7.0.0 <7.3.0',
        'imap' => '>=7.0.0 <7.3.0',
        'intl' => '>=7.0.0 <7.3.0',
        'ldap' => '>=7.0.0 <7.3.0',
        'mailparse' => '>=7.0.0 <7.3.0',
        'mcrypt' => '>=7.0.0 <7.2.0',
        'msgpack' => '>=7.0.0 <7.3.0',
        'mysqli' => '>=7.0.0 <7.3.0',
        'oauth' => '>=7.0.0 <7.3.0',
        'opcache' => '>=7.0.0 <7.3.0',
        'pcntl' => '>=7.0.0 <7.3.0',
        'pdo_mysql' => '>=7.0.0 <7.3.0',
        'propro' => '>=7.0.0 <7.3.0',
        'pspell' => '>=7.0.0 <7.3.0',
        'raphf' => '>=7.0.0 <7.3.0',
        'recode' => '>=7.0.0 <7.3.0',
        'redis' => '>=7.0.0 <7.3.0',
        'shmop' => '>=7.0.0 <7.3.0',
        'soap' => '>=7.0.0 <7.3.0',
        'sockets' => '>=7.0.0 <7.3.0',
        'sodium' => '>=7.0.0 <7.3.0',
        'ssh2' => '>=7.0.0 <7.3.0',
        'sysvmsg' => '>=7.0.0 <7.3.0',
        'sysvsem' => '>=7.0.0 <7.3.0',
        'sysvshm' => '>=7.0.0 <7.3.0',
        'tidy' => '>=7.0.0 <7.3.0',
        'xdebug' => '>=7.0.0 <7.3.0',
        'xmlrpc' => '>=7.0.0 <7.3.0',
        'xsl' => '>=7.0.0 <7.3.0',
        'yaml' => '>=7.0.0 <7.3.0',
        'zip' => '>=7.0.0 <7.3.0',
    ];

    /**
     * Extensions which built-in and can't be uninstalled
     */
    const BUILTIN_EXTENSIONS = [
        'ctype' => '>=7.0.0 <7.3.0',
        'curl' => '>=7.0.0 <7.3.0',
        'date' => '>=7.0.0 <7.3.0',
        'dom' => '>=7.0.0 <7.3.0',
        'fileinfo' => '>=7.0.0 <7.3.0',
        'filter' => '>=7.0.0 <7.3.0',
        'ftp' => '>=7.0.0 <7.3.0',
        'hash' => '>=7.0.0 <7.3.0',
        'iconv' => '>=7.0.0 <7.3.0',
        'json' => '>=7.0.0 <7.3.0',
        'mbstring' => '>=7.0.0 <7.3.0',
        'mysqlnd' => '>=7.0.0 <7.3.0',
        'openssl' => '>=7.0.0 <7.3.0',
        'pcre' => '>=7.0.0 <7.3.0',
        'pdo' => '>=7.0.0 <7.3.0',
        'pdo_sqlite' => '>=7.0.0 <7.3.0',
        'phar' => '>=7.0.0 <7.3.0',
        'posix' => '>=7.0.0 <7.3.0',
        'readline' => '>=7.0.0 <7.3.0',
        'session' => '>=7.0.0 <7.3.0',
        'simplexml' => '>=7.0.0 <7.3.0',
        'sqlite3' => '>=7.0.0 <7.3.0',
        'tokenizer' => '>=7.0.0 <7.3.0',
        'xml' => '>=7.0.0 <7.3.0',
        'xmlreader' => '>=7.0.0 <7.3.0',
        'xmlwriter' => '>=7.0.0 <7.3.0',
        'zlib' => '>=7.0.0 <7.3.0',
    ];

    /**
     * Extensions which should be ignored
     */
    const IGNORED_EXTENSIONS = ['blackfire', 'newrelic'];

    /**
     * @var VersionParser
     */
    private $versionParser;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @param VersionParser $versionParser
     */
    public function __construct(Config $config, VersionParser $versionParser)
    {
        $this->config = $config;
        $this->versionParser = $versionParser;
    }

    /**
     * Returns list of PHP extensions which will be enabled in Docker PHP container.
     *
     * @param string $phpVersion
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function get(string $phpVersion): array
    {
        $phpConstraint = new Constraint('==', $this->versionParser->normalize($phpVersion));
        $phpExtensions = array_diff(
            array_unique(
                array_merge(self::DEFAULT_PHP_EXTENSIONS, $this->config->getEnabledPhpExtensions())
            ),
            $this->config->getDisabledPhpExtensions(),
            self::IGNORED_EXTENSIONS
        );
        $messages = [];
        $result = [];
        foreach ($phpExtensions as $phpExtName) {
            if (isset(self::BUILTIN_EXTENSIONS[$phpExtName])) {
                $phpExtConstraint = $this->versionParser->parseConstraints(self::BUILTIN_EXTENSIONS[$phpExtName]);
                if ($phpConstraint->matches($phpExtConstraint)) {
                    continue;
                }
            }
            if (isset(self::AVAILABLE_PHP_EXTENSIONS[$phpExtName])) {
                $phpExtConstraintAvailable = $this->versionParser->parseConstraints(
                    self::AVAILABLE_PHP_EXTENSIONS[$phpExtName]
                );
                if ($phpConstraint->matches($phpExtConstraintAvailable)) {
                    $result[] = $phpExtName;
                    continue;
                }
                $messages[] = "PHP extension $phpExtName is not available for PHP version $phpVersion.";
                continue;
            }
            $messages[] = "PHP extension $phpExtName is not supported.";
        }
        if (!empty($messages)) {
            throw new ConfigurationMismatchException(implode(PHP_EOL, $messages));
        }
        return $result;
    }
}
