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
     * @param string $root
     * @param string $magentoRoot
     */
    public function __construct(string $root, string $magentoRoot)
    {
        $this->root = realpath($root);
        $this->magentoRoot = realpath($magentoRoot);
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
