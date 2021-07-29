<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\Php;

use Composer\Semver\Semver;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Returns list of PHP extensions which will be enabled in Docker PHP container.
 */
class ExtensionResolver
{
    public const EXTENSION_OS_DEPENDENCIES = 'extension_os_dependencies';
    public const EXTENSION_PACKAGE_NAME = 'extension_package_name';
    public const EXTENSION_TYPE = 'extension_type';
    public const EXTENSION_TYPE_PECL = 'extension_type_pecl';
    public const EXTENSION_TYPE_CORE = 'extension_type_core';
    public const EXTENSION_TYPE_INSTALLATION_SCRIPT = 'extension_type_installation_script';
    public const EXTENSION_CONFIGURE_OPTIONS = 'extension_configure_options';
    public const EXTENSION_INSTALLATION_SCRIPT = 'extension_installation_script';

    /**
     * Extensions which should be installed by default
     */
    public const DEFAULT_PHP_EXTENSIONS = [
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
        // Required for Magento 2.4.0+
        'xsl',
        'sodium'
    ];

    /**
     * Extensions which built-in and can't be uninstalled
     */
    private const BUILTIN_EXTENSIONS = [
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
    private const IGNORED_EXTENSIONS = ['newrelic'];

    /**
     * @var Semver
     */
    private $semver;

    /**
     * @param Semver $semver
     */
    public function __construct(Semver $semver)
    {
        $this->semver = $semver;
    }

    /**
     * Returns list of PHP extensions which will be enabled in Docker PHP container.
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    public function get(Config $config): array
    {
        $phpVersion = $config->getServiceVersion(ServiceInterface::SERVICE_PHP);
        $enabledPhpExtensions = [];
        foreach ($config->getEnabledPhpExtensions() as $phpExtension) {
            is_array($phpExtension) ? : array_push($enabledPhpExtensions, $phpExtension);
        }
        $enabledExtensions = array_unique(
            array_merge(self::DEFAULT_PHP_EXTENSIONS, $enabledPhpExtensions)
        );
        $phpExtensions = array_diff(
            $enabledExtensions,
            $config->getDisabledPhpExtensions(),
            self::IGNORED_EXTENSIONS
        );
        $messages = [];
        $result = [];

        foreach ($phpExtensions as $phpExtName) {
            if (isset(self::BUILTIN_EXTENSIONS[$phpExtName])
                && $this->semver::satisfies($phpVersion, self::BUILTIN_EXTENSIONS[$phpExtName])
            ) {
                continue;
            }

            if (isset(self::getConfig()[$phpExtName])) {
                $constraint = implode('||', array_keys(self::getConfig()[$phpExtName]));

                if ($this->semver::satisfies($phpVersion, $constraint)) {
                    $result[] = $phpExtName;
                    continue;
                }
                $messages[] = "PHP extension $phpExtName is not available for PHP version $phpVersion.";
                continue;
            }
            $messages[] = "PHP extension $phpExtName is not supported.";
        }

        if ($messages) {
            throw new ConfigurationMismatchException(implode(PHP_EOL, $messages));
        }

        return $result;
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public static function getConfig(): array
    {
        return [
            'bcmath' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'blackfire' => [
                '>=7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_INSTALLATION_SCRIPT,
                    // phpcs:disable
                    self::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
curl -A "Docker" -o /tmp/blackfire-probe.tar.gz -D - -L -s https://blackfire.io/api/v1/releases/probe/php/linux/amd64/$(php -r "echo PHP_MAJOR_VERSION.PHP_MINOR_VERSION;")
mkdir -p /tmp/blackfire
tar zxpf /tmp/blackfire-probe.tar.gz -C /tmp/blackfire
mv /tmp/blackfire/blackfire-*.so $(php -r "echo ini_get ('extension_dir');")/blackfire.so
echo blackfire.agent_socket=tcp://blackfire:8707 > $(php -i | grep "additional .ini" | awk '{print $9}')/blackfire.ini
rm -rf /tmp/blackfire /tmp/blackfire-probe.tar.gz
BASH
// phpcs:enable
                ]
            ],
            'bz2' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libbz2-dev'],
                ],
            ],
            'calendar' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'exif' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'gd' => [
                '>=7.0 <=7.3' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libjpeg62-turbo-dev', 'libpng-dev', 'libfreetype6-dev'],
                    self::EXTENSION_CONFIGURE_OPTIONS => [
                        '--with-freetype-dir=/usr/include/',
                        '--with-jpeg-dir=/usr/include/'
                    ],
                ],
                '>=7.4' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libjpeg62-turbo-dev', 'libpng-dev', 'libfreetype6-dev'],
                    self::EXTENSION_CONFIGURE_OPTIONS => [
                        '--with-freetype=/usr/include/',
                        '--with-jpeg=/usr/include/'
                    ],
                ],
            ],
            'geoip' => [
                '>=7.0 <8.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libgeoip-dev', 'wget'],
                    self::EXTENSION_PACKAGE_NAME => 'geoip-1.1.1',
                ],
            ],
            'gettext' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'gmp' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libgmp-dev'],
                ],
            ],
            'gnupg' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libgpgme11-dev'],
                ],
            ],
            'igbinary' => [
                '>=7.0 <8.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'imagick' => [
                '>=7.0 <8.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libmagickwand-dev', 'libmagickcore-dev'],
                ],
            ],
            'imap' => [
                '>=7.0 <=7.3'  => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libc-client-dev', 'libkrb5-dev'],
                    self::EXTENSION_CONFIGURE_OPTIONS => ['--with-kerberos', '--with-imap-ssl'],
                ],
            ],
            'intl' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libicu-dev'],
                ],
            ],
            'ldap' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libldap2-dev'],
                    self::EXTENSION_CONFIGURE_OPTIONS => ['--with-libdir=lib/x86_64-linux-gnu'],
                ],
            ],
            'mailparse' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'mcrypt' => [
                '>=7.0.0 <7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libmcrypt-dev'],
                ],
            ],
            'msgpack' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'mysqli' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'oauth' => [
                '>=7.0 <8.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
                '>=8.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libpcre3-dev'],
                ],
            ],
            'opcache' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_CONFIGURE_OPTIONS => ['--enable-opcache'],
                ],
            ],
            'pcov' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'pdo_mysql' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'propro' => [
                '>=7.0 <8.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'pspell' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libpspell-dev'],
                ],
            ],
            'raphf' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'recode' => [
                '>=7.0 <=7.3' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['librecode0', 'librecode-dev'],
                ],
            ],
            'redis' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL],
            ],
            'shmop' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'soap' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'sockets' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'zookeeper' => [
                '>=7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_INSTALLATION_SCRIPT,
                    // phpcs:disable
                    self::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
mkdir -p /tmp/zoo
cd /tmp/zoo
git clone https://github.com/php-zookeeper/php-zookeeper.git
curl -LO https://archive.apache.org/dist/zookeeper/zookeeper-3.4.14/zookeeper-3.4.14.tar.gz
tar -xf zookeeper-3.4.14.tar.gz
cp zookeeper-3.4.14/zookeeper-client/zookeeper-client-c/generated/zookeeper.jute.h zookeeper-3.4.14/zookeeper-client/zookeeper-client-c/include
cd zookeeper-3.4.14/zookeeper-client/zookeeper-client-c
./configure
sed -i 's/CFLAGS = -g -O2 -D_GNU_SOURCE/CFLAGS = -g -O2 -D_GNU_SOURCE -Wno-error=format-overflow -Wno-error=stringop-truncation/g' Makefile
make
make install
ldconfig
cd /tmp/zoo/php-zookeeper
phpize
./configure --with-libzookeeper-dir=../zookeeper-3.4.14/zookeeper-client/zookeeper-client-c
make
make install
BASH
// phpcs:enable
                ],
            ],
            'sodium' => [
                '>=7.0 <7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_INSTALLATION_SCRIPT,
                    self::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
mkdir -p /tmp/libsodium
curl -sL https://github.com/jedisct1/libsodium/archive/1.0.18-RELEASE.tar.gz | tar xzf - -C  /tmp/libsodium
cd /tmp/libsodium/libsodium-1.0.18-RELEASE/
./configure
make && make check
make install
cd /
rm -rf /tmp/libsodium
pecl install -o -f libsodium
BASH
                ],
                '>=7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_INSTALLATION_SCRIPT,
                    self::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
rm -f /usr/local/etc/php/conf.d/*sodium.ini
rm -f /usr/local/lib/php/extensions/*/*sodium.so
apt-get remove libsodium* -y
mkdir -p /tmp/libsodium
curl -sL https://github.com/jedisct1/libsodium/archive/1.0.18-RELEASE.tar.gz | tar xzf - -C  /tmp/libsodium
cd /tmp/libsodium/libsodium-1.0.18-RELEASE/
./configure
make && make check
make install
cd /
rm -rf /tmp/libsodium
pecl install -o -f libsodium
BASH
                ]
            ],
            'ssh2' => [
                // SSH2 is not supported on 7.3
                // https://serverpilot.io/docs/how-to-install-the-php-ssh2-extension
                '>=7.0 <7.3' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libssh2-1', 'libssh2-1-dev'],
                    self::EXTENSION_PACKAGE_NAME => 'ssh2-1.1.2',
                ],
            ],
            'sysvmsg' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'sysvsem' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'sysvshm' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'tidy' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libtidy-dev'],
                ],
            ],
            'xdebug' => [
                '>=7.2' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_PACKAGE_NAME => 'xdebug-3.0.4',
                ],
            ],
            'xmlrpc' => [
                '>=7.0 <8.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
                '>=8.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_PACKAGE_NAME => 'xmlrpc-1.0.0RC2',
                ],
            ],
            'xsl' => [
                '>=7.0' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libxslt1-dev'],
                ],
            ],
            'yaml' => [
                '>=7.0 <7.1' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libyaml-dev'],
                    self::EXTENSION_PACKAGE_NAME => 'yaml-2.1.0',
                ],
                '>=7.1' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_PECL,
                    self::EXTENSION_OS_DEPENDENCIES => ['libyaml-dev'],
                ],
            ],
            'zip' => [
                '>=7.0 <=7.3' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libzip-dev', 'zip'],
                    self::EXTENSION_CONFIGURE_OPTIONS => ['--with-libzip'],
                ],
                '>=7.4' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE,
                    self::EXTENSION_OS_DEPENDENCIES => ['libzip-dev', 'zip'],
                ],
            ],
            'pcntl' => [
                '>=7.0' => [self::EXTENSION_TYPE => self::EXTENSION_TYPE_CORE],
            ],
            'ioncube' => [
                '>=7.0 <=7.4' => [
                    self::EXTENSION_TYPE => self::EXTENSION_TYPE_INSTALLATION_SCRIPT,
                    self::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
cd /tmp
curl -O https://downloads.ioncube.com/loader_downloads/ioncube_loaders_lin_x86-64.tar.gz
tar zxvf ioncube_loaders_lin_x86-64.tar.gz
export PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
export PHP_EXT_DIR=$(php-config --extension-dir)
cp "./ioncube/ioncube_loader_lin_\${PHP_VERSION}.so" "\${PHP_EXT_DIR}/ioncube.so"
rm -rf ./ioncube
rm ioncube_loaders_lin_x86-64.tar.gz
BASH
                ],
            ],
        ];
    }
}
