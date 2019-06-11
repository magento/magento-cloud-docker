<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
use Mcd\Command\Generate\Php;

return [
//        'amqp' => [],
//        'apc' => [],
//        'apcu' => [],
//        'apcu_bc' => [],
//        'applepay' => [],
    'bcmath' => ['7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
//        'blackfire' => [],
//        'bz2' => [],
//        'calendar' => [],
//        'common' => [],
//        'ctype' => [],
+//        'curl' => [],
//        'dba' => [],
    /**
     * ext-dom is installed by default https://www.php.net/manual/en/dom.installation.php
     */
//        'dom' => [
//            '7.*' => [
//                Php::EXTENSION_OS_DEPENDENCIES => ['libxml2'],
//                Php::EXTENSION_PACKAGE_NAME => 'dom',
//                Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
//
//            ]
//        ],

//        'enchant' => [],
//        'event' => [],
//        'exif' => [],
//        'fileinfo' => [],
//        'ftp' => [],
    'gd' => [
        '*' => [
            Php::EXTENSION_OS_DEPENDENCIES => ['libjpeg62-turbo-dev', 'libpng-dev', 'libfreetype6-dev'],
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
            Php::EXTENSION_CONFIGURE_OPTIONS => [
                '--with-freetype-dir=/usr/include/',
                '--with-jpeg-dir=/usr/include/',
            ]
        ]
    ],
//        'gearman' => [],
//        'geoip' => [],
//        'gettext' => [],
//        'gmp' => [],
//        'http' => [],
//        'iconv' => [],
//        'igbinary' => [],
//        'imagick' => [],
//        'imap' => [],
//        'interbase' => [],
    'intl' => [
        '*' => [
            Php::EXTENSION_OS_DEPENDENCIES => ['libicu-dev'],
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
        ]
    ],
//        'ioncube' => [],
//        'json' => [],
//        'ldap' => [],
//        'mailparse' => [],
    /**
     * ext-mbstring is installed by default https://github.com/docker-library/php/blob/e63194a0006848edb13b7eff5a7f9d790d679428/7.1/jessie/cli/Dockerfile#L151
     */
//        'mbstring' => [],
    'mcrypt' => [
        '7.0.* | 7.1.*' => [
            Php::EXTENSION_OS_DEPENDENCIES => ['libmcrypt-dev'],
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
        ]
    ],
//        'memcache' => [],
//        'memcached' => [],
//        'mongo' => [],
//        'mongodb' => [],
//        'msgpack' => [],
//        'mssql' => [],
+//        'mysql' => [],
//        'mysqli' => [],
//        'mysqlnd' => [],
//        'newrelic' => [],
//        'oauth' => [],
//        'odbc' => [],
    'opcache' => ['*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
//        'openssl' => [],
//        'pdo' => [],
//        'pdo_dblib' => [],
//        'pdo_firebird' => [],
    'pdo_mysql' => ['*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
//        'pdo_odbc' => [],
//        'pdo_pgsql' => [],
//        'pdo_sqlite' => [],
//        'pdo_sqlsrv' => [],
//        'pgsql' => [],
//        'phar' => [],
//        'pinba' => [],
//        'posix' => [],
//        'propro' => [],
//        'pspell' => [],
//        'pthreads' => [],
//        'raphf' => [],
//        'readline' => [],
//        'recode' => [],
//        'redis' => [],
//        'shmop' => [],
//        'simplexml' => [],
//        'snmp' => [],
    'soap' => ['7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
    'sockets' => ['7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
//        'sodium' => [],
//        'sourceguardian' => [],
//        'spplus' => [],
//        'sqlite3' => [],
//        'sqlsrv' => [],
    'ssh2' => [
        '7.*' => [
            Php::EXTENSION_OS_DEPENDENCIES => ['libssh2-1', 'libssh2-1-dev'],
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL,
            Php::EXTENSION_PACKAGE_NAME => 'ssh2-1.1.2',
        ]
    ],
//        'sybase' => [],
//        'sysvmsg' => [],
//        'sysvsem' => [],
//        'sysvshm' => [],
//        'tideways' => [],
//        'tidy' => [],
//        'tokenizer' => [],
//        'uuid' => [],
//        'wddx' => [],
//        'xcache' => [],
    'xdebug' => ["*" => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_PECL]],
//        'xhprof' => [],
//        'xml' => [],
//        'xmlreader' => [],
//        'xmlrpc' => [],
//        'xmlwriter' => [],
    'xsl' => [
        '*' => [
            Php::EXTENSION_OS_DEPENDENCIES => ['libxslt1-dev'],
            Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE,
        ]
    ],
//        'yaml' => [],
//        'zbarcode' => [],
//        'zendopcache' => [],
    'zip' => ['7.*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
    'pcntl' => ['*' => [Php::EXTENSION_TYPE => Php::EXTENSION_TYPE_CORE]],
];
