<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\Php;

use Composer\Semver\Semver;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\Config;

/**
 * Returns list of PHP extensions which will be enabled in Docker PHP container.
 */
class ExtensionResolver
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
        'bcmath' => '>=7.0',
        'bz2' => '>=7.0',
        'calendar' => '>=7.0',
        'exif' => '>=7.0',
        'gd' => '>=7.0',
        'geoip' => '>=7.0',
        'gettext' => '>=7.0',
        'gmp' => '>=7.0',
        'igbinary' => '>=7.0',
        'imagick' => '>=7.0',
        'imap' => '>=7.0',
        'intl' => '>=7.0',
        'ldap' => '>=7.0',
        'mailparse' => '>=7.0',
        'mcrypt' => '>=7.0 <7.2.0',
        'msgpack' => '>=7.0',
        'mysqli' => '>=7.0',
        'oauth' => '>=7.0',
        'opcache' => '>=7.0',
        'pcntl' => '>=7.0',
        'pdo_mysql' => '>=7.0',
        'propro' => '>=7.0',
        'pspell' => '>=7.0',
        'raphf' => '>=7.0',
        'recode' => '>=7.0',
        'redis' => '>=7.0',
        'shmop' => '>=7.0',
        'soap' => '>=7.0',
        'sockets' => '>=7.0',
        'sodium' => '>=7.0',
        'ssh2' => '>=7.0',
        'sysvmsg' => '>=7.0',
        'sysvsem' => '>=7.0',
        'sysvshm' => '>=7.0',
        'tidy' => '>=7.0',
        'xdebug' => '>=7.0',
        'xmlrpc' => '>=7.0',
        'xsl' => '>=7.0',
        'yaml' => '>=7.0',
        'zip' => '>=7.0',
    ];

    /**
     * Extensions which built-in and can't be uninstalled
     */
    const BUILTIN_EXTENSIONS = [
        'ctype' => '>=7.0',
        'curl' => '>=7.0',
        'date' => '>=7.0',
        'dom' => '>=7.0',
        'fileinfo' => '>=7.0',
        'filter' => '>=7.0',
        'ftp' => '>=7.0',
        'hash' => '>=7.0',
        'iconv' => '>=7.0',
        'json' => '>=7.0',
        'mbstring' => '>=7.0',
        'mysqlnd' => '>=7.0',
        'openssl' => '>=7.0',
        'pcre' => '>=7.0',
        'pdo' => '>=7.0',
        'pdo_sqlite' => '>=7.0',
        'phar' => '>=7.0',
        'posix' => '>=7.0',
        'readline' => '>=7.0',
        'session' => '>=7.0',
        'simplexml' => '>=7.0',
        'sqlite3' => '>=7.0',
        'tokenizer' => '>=7.0',
        'xml' => '>=7.0',
        'xmlreader' => '>=7.0',
        'xmlwriter' => '>=7.0',
        'zlib' => '>=7.0',
    ];

    /**
     * Extensions which should be ignored
     */
    const IGNORED_EXTENSIONS = ['blackfire', 'newrelic'];

    /**
     * @var Semver
     */
    private $semver;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param Config $config
     * @param Semver $semver
     */
    public function __construct(Config $config, Semver $semver)
    {
        $this->config = $config;
        $this->semver = $semver;
    }

    /**
     * Returns list of PHP extensions which will be enabled in Docker PHP container.
     *
     * @param string $string
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function get(string $string): array
    {
        $enabledExtensions = array_unique(
            array_merge(self::DEFAULT_PHP_EXTENSIONS, $this->config->getEnabledPhpExtensions())
        );
        $phpExtensions = array_diff(
            $enabledExtensions,
            $this->config->getDisabledPhpExtensions(),
            self::IGNORED_EXTENSIONS
        );
        $messages = [];
        $result = [];

        foreach ($phpExtensions as $phpExtName) {
            if (isset(self::BUILTIN_EXTENSIONS[$phpExtName])
                && $this->semver::satisfies($string, self::BUILTIN_EXTENSIONS[$phpExtName])
            ) {
                continue;
            }

            if (isset(self::AVAILABLE_PHP_EXTENSIONS[$phpExtName])) {
                if ($this->semver::satisfies($string, self::AVAILABLE_PHP_EXTENSIONS[$phpExtName])) {
                    $result[] = $phpExtName;
                    continue;
                }
                $messages[] = "PHP extension $phpExtName is not available for PHP version $string.";
                continue;
            }
            $messages[] = "PHP extension $phpExtName is not supported.";
        }

        if ($messages) {
            throw new ConfigurationMismatchException(implode(PHP_EOL, $messages));
        }

        return $result;
    }
}
