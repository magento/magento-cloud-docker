<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Environment\Shared;

use http\Env;
use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Config\ReaderInterface;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Config\EnvCoder;

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
     * @var EnvCoder
     */
    private $envCoder;

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param EnvCoder $envCoder
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        EnvCoder $envCoder
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->envCoder = $envCoder;
    }

    /**
     * @inheritDoc
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
