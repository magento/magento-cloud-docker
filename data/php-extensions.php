<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

use Mcd\Command\Generate\Php;

return [
    'bcmath' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'bz2' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libbz2-dev'],
        ],
    ],
    'calendar' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'dba' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'exif' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'gd' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libjpeg62-turbo-dev', 'libpng-dev', 'libfreetype6-dev'],
            Php::EXTENSION_CONFIGURE_OPTIONS => ['--with-freetype-dir=/usr/include/', '--with-jpeg-dir=/usr/include/'],
        ],
    ],
    'geoip' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_OS_DEPENDENCIES => ['libgeoip-dev', 'wget'],
            Php::EXTENSION_PACKAGE_NAME => 'geoip-1.1.1',
        ],
    ],
    'gettext' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'gmp' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libgmp-dev'],
        ],
    ],
    'igbinary' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'imagick' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_OS_DEPENDENCIES => ['libmagickwand-dev', 'libmagickcore-dev'],
        ],
    ],
    'imap' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libc-client-dev', 'libkrb5-dev'],
            Php::EXTENSION_CONFIGURE_OPTIONS => ['--with-kerberos', '--with-imap-ssl'],
        ],
    ],
    'intl' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libicu-dev'],
        ],
    ],
    'ldap' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libldap2-dev'],
            Php::EXTENSION_CONFIGURE_OPTIONS => ['--with-libdir=lib/x86_64-linux-gnu'],
        ],
    ],
    'mailparse' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'mcrypt' => [
        '7.0.* | 7.1.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libmcrypt-dev'],
        ],
    ],
    'msgpack' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'mysqli' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'oauth' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'opcache' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_CONFIGURE_OPTIONS => ['--enable-opcache'],
        ],
    ],
    'pdo_mysql' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'pdo_pgsql' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libpq-dev'],
        ],
    ],
    'pgsql' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libpq-dev'],
        ],
    ],
    'propro' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'pspell' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libpspell-dev'],
        ],
    ],
    'raphf' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'recode' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['librecode0', 'librecode-dev'],
        ],
    ],
    'redis' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'shmop' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'soap' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'sockets' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'sodium' => [
        '7.2.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libsodium-dev'],
        ],
        '7.0.* | 7.1.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_OS_DEPENDENCIES => ['libsodium-dev'],
            Php::EXTENSION_PACKAGE_NAME => 'libsodium',
        ]
    ],
    'ssh2' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_OS_DEPENDENCIES => ['libssh2-1', 'libssh2-1-dev'],
            Php::EXTENSION_PACKAGE_NAME => 'ssh2-1.1.2',
        ],
    ],
    'sysvmsg' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'sysvsem' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'sysvshm' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'tidy' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libtidy-dev'],
        ],
    ],
    'wddx' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'xdebug' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL],
    ],
    'xmlrpc' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'xsl' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_OS_DEPENDENCIES => ['libxslt1-dev'],
        ],
    ],
    'yaml' => [
        '7.*' => [
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_OS_DEPENDENCIES => ['libyaml-dev'],
        ],
    ],
    'zip' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
    'pcntl' => [
        '7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE],
    ],
//    'amqp' => [],
//    'apc' => [],
//    'apcu' => [],
//    'apcu_bc' => [],
//    'applepay' => [],
//    'blackfire' => [],
//    'common' => [],
//    'ctype' => [],
//    'curl' => [],
//    'dom' => [],
//    'enchant' => [],
//    'event' => [],
//    'fileinfo' => [],
//    'ftp' => [],
//    'gearman' => [],
//    'http' => [],
//    'iconv' => [],
//    'interbase' => [],
//    'ioncube' => [],
//    'json' => [],
//    'mbstring' => [],
//    'memcache' => [],
//    'memcached' => [],
//    'mongo' => [],
//    'mongodb' => [],
//    'mssql' => [],
//    'mysql' => [],
//    'mysqlnd' => [],
//    'newrelic' => [],
//    'odbc' => [],
//    'openssl' => [],
//    'pdo' => [],
//    'pdo_dblib' => [],
//    'pdo_firebird' => [],
//    'pdo_odbc' => [],
//    'pdo_sqlite' => [],
//    'pdo_sqlsrv' => [],
//    'phar' => [],
//    'pinba' => [],
//    'posix' => [],
//    'pthreads' => [],
//    'readline' => [],
//    'simplexml' => [],
//    'snmp' => [],
//    'sourceguardian' => [],
//    'spplus' => [],
//    'sqlite3' => [],
//    'sqlsrv' => [],
//    'sybase' => [],
//    'tideways' => [],
//    'tokenizer' => [],
//    'uuid' => [],
//    'xcache' => [],
//    'xhprof' => [],
//    'xml' => [],
//    'xmlreader' => [],
//    'xmlwriter' => [],
//    'zbarcode' => [],
//    'zendopcache' => [],
];
