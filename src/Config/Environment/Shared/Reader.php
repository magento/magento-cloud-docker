<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Environment\Shared;

use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Config\ReaderInterface;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Config\Environment\Encoder;

/**
 * Reader of config.php and config.php.dist files.
 */
class Reader implements ReaderInterface
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Encoder
     */
    private $envCoder;

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param Encoder $envCoder
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        Encoder $envCoder
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->envCoder = $envCoder;
    }

    /**
     * Reads config.php file and returns array of configured environment variables.
     *
     * If file does not exist returns empty array.
     * If it cannot read existing file throws exception.
     *
     * @return array
     * @throws FilesystemException
     */
    public function read(): array
    {
        $sourcePath = $this->directoryList->getDockerRoot() . '/config.php';

        try {
            if ($this->filesystem->exists($sourcePath)) {
                return $this->envCoder->decode($this->filesystem->getRequire($sourcePath));
            }
        } catch (FileNotFoundException $exception) {
            throw new FilesystemException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return [];
    }
}
