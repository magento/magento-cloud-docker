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
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    /**
     * @inheritDoc
     */
    public function read(): array
    {
        $sourcePath = $this->directoryList->getDockerRoot() . '/config.php';

        if (!$this->filesystem->exists($sourcePath)) {
            $sourcePath .= '.dist';
        }

        try {
            if ($this->filesystem->exists($sourcePath)) {
                return $this->filesystem->getRequire($sourcePath);
            }
        } catch (FileNotFoundException $exception) {
            throw new FilesystemException($exception->getMessage(), $exception->getCode(), $exception);
        }

        throw new FilesystemException(sprintf(
            'Source file %s does not exists',
            $sourcePath
        ));
    }
}
