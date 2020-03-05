<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Filesystem;

/**
 * Resolver of file configurations.
 */
class FileList
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param DirectoryList $directoryList
     */
    public function __construct(DirectoryList $directoryList)
    {
        $this->directoryList = $directoryList;
    }

    /**
     * @return string
     */
    public function getMagentoDockerCompose(): string
    {
        return $this->directoryList->getMagentoRoot() . '/docker-compose.yml';
    }

    /**
     * @return string
     */
    public function getAppConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.app.yaml';
    }

    /**
     * @return string
     */
    public function getServicesConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento/services.yaml';
    }

    /**
     * @return string
     */
    public function getEceToolsCompose(): string
    {
        return $this->directoryList->getEceToolsRoot() . '/docker-compose.yml';
    }

    /**
     * @return string
     */
    public function getComposer(): string
    {
        return $this->directoryList->getRoot() . '/composer.json';
    }

    /**
     * @return string
     */
    public function getDockerConfig(): string
    {
        return $this->directoryList->getMagentoRoot() . '/.magento.docker.yaml';
    }
}
