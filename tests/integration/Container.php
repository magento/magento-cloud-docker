<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Test\Integration;

use Magento\CloudDocker\App\ContainerException;

/**
 * Container for Integration Tests
 */
class Container extends \Magento\CloudDocker\App\Container
{
    /**
     * @var self[]
     */
    private static $instances = [];

    /**
     * @param string $root
     * @param string $magentoBasePath
     * @param string|null $toolsBasePath
     * @return static
     * @throws ContainerException
     */
    public static function getInstance(
        string $root,
        string $magentoBasePath,
        ?string $toolsBasePath = null
    ): self {
        $key = crc32($root . $magentoBasePath . $toolsBasePath);

        if (!array_key_exists($key, self::$instances)) {
            self::$instances[$key] = new self($root, $magentoBasePath, $toolsBasePath);
        }

        return self::$instances[$key];
    }
}
