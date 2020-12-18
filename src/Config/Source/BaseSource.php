<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Composer\IO\NullIO;
use Composer\Factory;
use Illuminate\Config\Repository;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;

/**
 * The very base source for most of other sources
 */
class BaseSource implements SourceInterface
{
    public const INSTALLATION_TYPE_GIT = 'git';
    public const INSTALLATION_TYPE_COMPOSER = 'composer';

    public const DEFAULT_HOST = 'magento2.docker';
    public const DEFAULT_PORT = '80';
    public const DEFAULT_TLS_PORT = '443';
    public const DEFAULT_MAILHOG_SMTP_PORT = '1025';
    public const DEFAULT_MAILHOG_HTTP_PORT = '8025';
    public const DEFAULT_NGINX_WORKER_PROCESSES = '1';
    public const DEFAULT_NGINX_WORKER_CONNECTIONS = '1024';

    /**
     * @var EnvReader
     */
    private $envReader;

    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @param EnvReader $envReader
     * @param Filesystem $filesystem
     * @param FileList $fileList
     */
    public function __construct(
        EnvReader $envReader,
        Filesystem $filesystem,
        FileList $fileList
    ) {
        $this->envReader = $envReader;
        $this->filesystem = $filesystem;
        $this->fileList = $fileList;
    }

    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $config = new Repository();

        $config->set([
            self::SYSTEM_MODE => BuilderFactory::BUILDER_PRODUCTION,
            self::SYSTEM_SYNC_ENGINE => null,
            self::CRON_ENABLED => false,
            self::SYSTEM_PORT => self::DEFAULT_PORT,
            self::SYSTEM_HOST => self::DEFAULT_HOST,
            self::SYSTEM_TLS_PORT => self::DEFAULT_TLS_PORT,
            self::INSTALLATION_TYPE => self::INSTALLATION_TYPE_COMPOSER,
            self::MAGENTO_VERSION => $this->getMagentoVersion(),
            self::SYSTEM_MAILHOG_SMTP_PORT => self::DEFAULT_MAILHOG_SMTP_PORT,
            self::SYSTEM_MAILHOG_HTTP_PORT => self::DEFAULT_MAILHOG_HTTP_PORT,
            self::SYSTEM_NGINX_WORKER_PROCESSES => self::DEFAULT_NGINX_WORKER_PROCESSES,
            self::SYSTEM_NGINX_WORKER_CONNECTIONS => self::DEFAULT_NGINX_WORKER_CONNECTIONS,
        ]);

        try {
            if ($variables = $this->envReader->read()) {
                $config->set(self::VARIABLES, $variables);
            }
        } catch (FilesystemException $exception) {
            throw new SourceException($exception->getMessage(), $exception->getCode(), $exception);
        }

        return $config;
    }

    /**
     * Gets Magento version from composer.json
     *
     * @return string|null
     */
    private function getMagentoVersion(): ?string
    {
        $composer = $this->fileList->getComposer();

        if ($this->filesystem->exists($composer)) {
            return Factory::create(new NullIO(), $this->fileList->getMagentoComposer())
                ->getPackage()
                ->getVersion();
        }

        return null;
    }
}
