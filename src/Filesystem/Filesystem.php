<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Filesystem;

use FilesystemIterator;

/**
 * Filesystem operations wrapper.
 */
class Filesystem
{
    /**
     * Write the content of a file.
     *
     * @param string $path
     * @param string $content
     * @return int|bool
     *
     * @throws FilesystemException
     */
    public function put(string $path, string $content)
    {
        $dirname = dirname($path);

        if (!$this->exists($dirname)) {
            $this->makeDirectory($dirname, 0755, true);
        }

        $result = @file_put_contents($path, $content);

        if (!$result) {
            throw new FilesystemException(sprintf(
                'The specified "%s" file could not be written %s',
                $path,
                $this->getWarningMessage()
            ));
        }

        return $result;
    }

    /**
     * Recursively delete a directory.
     *
     * The directory itself may be optionally preserved.
     *
     * @param string $directory
     * @param bool $preserve
     * @return bool
     */
    public function deleteDirectory($directory, $preserve = false): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $items = new FilesystemIterator($directory);

        foreach ($items as $item) {
            // If the item is a directory, we can just recurse into the function and
            // delete that sub-directory otherwise we'll just delete the file and
            // keep iterating through each file until the directory is cleaned.
            if ($item->isDir() && !$item->isLink()) {
                $this->deleteDirectory($item->getPathname());
            } else {
                $this->delete($item->getPathname());
            }
        }

        if (!$preserve) {
            @rmdir($directory);
        }

        return true;
    }

    /**
     * Determine if the given path is a directory.
     *
     * @param string $directory
     * @return bool
     */
    public function isDirectory($directory): bool
    {
        return is_dir($directory);
    }

    /**
     * Delete the file at a given path.
     *
     * @param string|array $paths
     * @return bool
     */
    public function delete($paths): bool
    {
        $paths = is_array($paths) ? $paths : func_get_args();

        $success = true;

        foreach ($paths as $path) {
            try {
                if (!@unlink($path)) {
                    $success = false;
                }
            } catch (\ErrorException $e) {
                $success = false;
            }
        }

        return $success;
    }

    /**
     * Create a directory.
     *
     * @param string $path
     * @param int $mode
     * @param bool $recursive
     * @param bool $force
     * @return bool
     */
    public function makeDirectory($path, $mode = 0755, $recursive = false, $force = false): bool
    {
        if ($force) {
            return @mkdir($path, $mode, $recursive);
        }

        return mkdir($path, $mode, $recursive);
    }

    /**
     * Copy a directory from one location to another.
     *
     * @param string $directory
     * @param string $destination
     * @param int|null $options
     * @param bool $recursive
     * @return bool
     */
    public function copyDirectory($directory, $destination, $options = null, $recursive = true): bool
    {
        if (!$this->isDirectory($directory)) {
            return false;
        }

        $options = $options ?: FilesystemIterator::SKIP_DOTS;

        // If the destination directory does not actually exist, we will go ahead and
        // create it recursively, which just gets the destination prepared to copy
        // the files over. Once we make the directory we'll proceed the copying.
        if (!$this->isDirectory($destination)) {
            $this->makeDirectory($destination, 0777, true);
        }

        $items = new FilesystemIterator($directory, $options);

        foreach ($items as $item) {
            // As we spin through items, we will check to see if the current file is actually
            // a directory or a file. When it is actually a directory we will need to call
            // back into this function recursively to keep copying these nested folders.
            $target = $destination . '/' . $item->getBasename();

            if ($item->isDir()) {
                if (!$recursive) {
                    continue;
                }

                $path = $item->getPathname();

                if (!$this->copyDirectory($path, $target, $options)) {
                    return false;
                }
            } else {
                if (!$this->copy($item->getPathname(), $target)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Get the contents of a file.
     *
     * @param string $path
     * @return string|false
     *
     * @throws FileNotFoundException
     */
    public function get($path)
    {
        if ($this->isFile($path)) {
            return file_get_contents($path);
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Copy a file to a new location.
     *
     * @param string $path
     * @param string $target
     * @return bool
     */
    public function copy($path, $target): bool
    {
        return copy($path, $target);
    }

    /**
     * Determine if the given path is a file.
     *
     * @param string $file
     * @return bool
     */
    public function isFile($file): bool
    {
        return is_file($file);
    }

    /**
     * Determine if a file or directory exists.
     *
     * @param string $path
     * @return bool
     */
    public function exists($path): bool
    {
        return file_exists($path);
    }

    /**
     * Get the returned value of a file.
     *
     * @param string $path
     * @return mixed
     *
     * @throws FileNotFoundException
     */
    public function getRequire($path)
    {
        if ($this->isFile($path)) {
            return require $path;
        }

        throw new FileNotFoundException("File does not exist at path {$path}");
    }

    /**
     * Changes file mode
     *
     * @param string $path
     * @param int $mode
     * @return bool
     */
    public function chmod(string $path, int $mode): bool
    {
        return chmod($path, $mode);
    }

    /**
     * Returns last warning message string
     *
     * @return string|null
     */
    private function getWarningMessage()
    {
        $warning = error_get_last();
        if ($warning && $warning['type'] == E_WARNING) {
            return 'Warning!' . $warning['message'];
        }

        return null;
    }
}
