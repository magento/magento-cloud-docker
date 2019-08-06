<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Magento\CloudDocker\Command\Image\GeneratePhp;

return [
    'bcmath' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'bz2' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libbz2-dev'],
        ],
    ],
    'calendar' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'exif' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'gd' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libjpeg62-turbo-dev', 'libpng-dev', 'libfreetype6-dev'],
            GeneratePhp::EXTENSION_CONFIGURE_OPTIONS => ['--with-freetype-dir=/usr/include/', '--with-jpeg-dir=/usr/include/'],
        ],
    ],
    'geoip' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libgeoip-dev', 'wget'],
            GeneratePhp::EXTENSION_PACKAGE_NAME => 'geoip-1.1.1',
        ],
    ],
    'gettext' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'gmp' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libgmp-dev'],
        ],
    ],
    'igbinary' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'imagick' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libmagickwand-dev', 'libmagickcore-dev'],
        ],
    ],
    'imap' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libc-client-dev', 'libkrb5-dev'],
            GeneratePhp::EXTENSION_CONFIGURE_OPTIONS => ['--with-kerberos', '--with-imap-ssl'],
        ],
    ],
    'intl' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libicu-dev'],
        ],
    ],
    'ldap' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libldap2-dev'],
            GeneratePhp::EXTENSION_CONFIGURE_OPTIONS => ['--with-libdir=lib/x86_64-linux-gnu'],
        ],
    ],
    'mailparse' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'mcrypt' => [
        '>=7.0.0 <7.2.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libmcrypt-dev'],
        ],
    ],
    'msgpack' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'mysqli' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'oauth' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'opcache' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_CONFIGURE_OPTIONS => ['--enable-opcache'],
        ],
    ],
    'pdo_mysql' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'propro' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'pspell' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libpspell-dev'],
        ],
    ],
    'raphf' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'recode' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['librecode0', 'librecode-dev'],
        ],
    ],
    'redis' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL],
    ],
    'shmop' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'soap' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'sockets' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'sodium' => [
        '>=7.0 <7.2' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_INSTALLATION_SCRIPT,
            GeneratePhp::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
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
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_INSTALLATION_SCRIPT,
            GeneratePhp::EXTENSION_INSTALLATION_SCRIPT => <<< BASH
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
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libssh2-1', 'libssh2-1-dev'],
            GeneratePhp::EXTENSION_PACKAGE_NAME => 'ssh2-1.1.2',
        ],
    ],
    'sysvmsg' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'sysvsem' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'sysvshm' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'tidy' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libtidy-dev'],
        ],
    ],
    'xdebug' => [
        '>=7.0 <7.3' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            // https://intellij-support.jetbrains.com/hc/en-us/community/posts/360003310760-XDebug-not-working-anymore
            // https://intellij-support.jetbrains.com/hc/en-us/community/posts/360003410140-PHPStorm-with-PHP7-3-and-xdebug-2-7-0
            GeneratePhp::EXTENSION_PACKAGE_NAME => 'xdebug-2.6.1',
        ],
        '>=7.3' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            GeneratePhp::EXTENSION_PACKAGE_NAME => 'xdebug-2.7.1',
        ],
    ],
    'xmlrpc' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
    'xsl' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libxslt1-dev'],
        ],
    ],
    'yaml' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_PECL,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libyaml-dev'],
        ],
    ],
    'zip' => [
        '>=7.0' => [
            GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE,
            GeneratePhp::EXTENSION_OS_DEPENDENCIES => ['libzip-dev', 'zip'],
            GeneratePhp::EXTENSION_CONFIGURE_OPTIONS => ['--with-libzip'],
        ],
    ],
    'pcntl' => [
        '>=7.0' => [GeneratePhp::EXTENSION_TYPE => GeneratePhp::EXTENSION_TYPE_CORE],
    ],
];
