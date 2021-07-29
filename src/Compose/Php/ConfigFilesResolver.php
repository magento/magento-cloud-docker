<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose\Php;

/**
 * Config files & folders resolver for different php version
 */
class ConfigFilesResolver
{
    public const FROM_PATH = 'from';
    public const TO_PATH = 'to';

    /**
     * Folders for copying
     */
    private const FOLDERS = [
        'cli' => ['bin'],
        'fpm' => [],
    ];

    /**
     * Config files for copying depends on php version
     */
    private const CONFIG_FILES = [
        'cli' => [
            'cli' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-cli.ini',
                    self::TO_PATH => 'etc/php-cli.ini',
                ],
            ],
            'xdebug' => [
                '>=7.2' => [
                    self::FROM_PATH => 'etc/php-xdebug-3.ini',
                    self::TO_PATH => 'etc/php-xdebug.ini',
                ],
            ],
            'pcov' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-pcov.ini',
                    self::TO_PATH => 'etc/php-pcov.ini',
                ],
            ],
            'mail' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/mail.ini',
                    self::TO_PATH => 'etc/mail.ini',
                ],
            ],
            'gnupg' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-gnupg.ini',
                    self::TO_PATH => 'etc/php-gnupg.ini',
                ],
            ],
        ],
        'fpm' => [
            'fpm' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-fpm.ini',
                    self::TO_PATH => 'etc/php-fpm.ini',
                ],
            ],
            'xdebug' => [
                '>=7.2' => [
                    self::FROM_PATH => 'etc/php-xdebug-3.ini',
                    self::TO_PATH => 'etc/php-xdebug.ini',
                ],
            ],
            'pcov' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-pcov.ini',
                    self::TO_PATH => 'etc/php-pcov.ini',
                ],
            ],
            'mail' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/mail.ini',
                    self::TO_PATH => 'etc/mail.ini',
                ],
            ],
            'fpm.conf' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-fpm.conf',
                    self::TO_PATH => 'etc/php-fpm.conf',
                ],
            ],
            'gnupg' => [
                '>=7.0' => [
                    self::FROM_PATH => 'etc/php-gnupg.ini',
                    self::TO_PATH => 'etc/php-gnupg.ini',
                ],
            ],
        ],
    ];

    /**
     * Returns the list of configuration files
     *
     * @param string $edition
     * @return array
     */
    public static function getConfigFiles(string $edition): array
    {
        return self::CONFIG_FILES[$edition];
    }

    /**
     * Returns folders for copying
     *
     * @param string $edition
     * @return array
     */
    public static function getFolders(string $edition): array
    {
        return self::FOLDERS[$edition];
    }
}
