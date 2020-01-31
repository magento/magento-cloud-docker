<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

/**
 * Resolver for system configuration.
 */
class Resolver
{
    /**
     * Resolves correct root path.
     *
     * @param string $path
     * @return string
     */
    public function getRootPath(string $path = '/'): string
    {
        /**
         * For Windows we'll define variable in .env file
         *
         * WINDOWS_PWD=//C/www/my-project
         */
        if (stripos(PHP_OS, 'win') === 0) {
            return '${WINDOWS_PWD}' . $path;
        }

        return '${PWD}' . $path;
    }
}
