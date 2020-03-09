<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;

/**
 * The very base source for most of other sources
 */
class BaseSource implements SourceInterface
{
    /**
     * @var EnvReader
     */
    private $envReader;

    /**
     * @param EnvReader $envReader
     */
    public function __construct(EnvReader $envReader)
    {
        $this->envReader = $envReader;
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
}
