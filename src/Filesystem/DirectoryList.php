<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Filesystem;

/**
 * Resolver for directory configurations.
 */
class DirectoryList
{
    /**
     * @var string
     */
    private $root;

    /**
     * @var string
     */
    private $magentoRoot;

    /**
     * @var string
     */
    private $eceRoot;

    /**
     * @param string $root
     * @param string $magentoRoot
     * @param string $eceRoot
     */
    public function __construct(string $root, string $magentoRoot, string $eceRoot = null)
    {
        $this->root = realpath($root);
        $this->magentoRoot = realpath($magentoRoot);
        $this->eceRoot = $eceRoot ? realpath($eceRoot) : null;
    }

    /**
     * @return string
     */
    public function getRoot(): string
    {
        return $this->root;
    }

    /**
     * @return string
     */
    public function getMagentoRoot(): string
    {
        return $this->magentoRoot;
    }

    /**
     * @return bool
     */
    public function hasEceToolsRoot(): bool
    {
        return $this->eceRoot !== null;
    }

    /**
     * @return string
     * @throws FilesystemException
     */
    public function getEceToolsRoot(): string
    {
        if (null === $this->eceRoot) {
            throw new FilesystemException('No ECE-Tools root defined');
        }

        return $this->eceRoot;
    }

    /**
     * @return string
     */
    public function getDockerRoot(): string
    {
        return $this->getMagentoRoot() . '/.docker';
    }

    /**
     * @return string
     */
    public function getImagesRoot(): string
    {
        return $this->getRoot() . '/images';
    }
}
