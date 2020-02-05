<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Reader;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Compose\DeveloperBuilder;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;
use Symfony\Component\Console\Input\InputInterface;

class CliReader implements ReaderInterface
{
    /**
     * Services.
     */
    public const OPTION_PHP = 'php';
    public const OPTION_NGINX = 'nginx';
    public const OPTION_DB = 'db';
    public const OPTION_EXPOSE_DB_PORT = 'expose-db-port';
    public const OPTION_REDIS = 'redis';
    public const OPTION_ES = 'es';
    public const OPTION_RABBIT_MQ = 'rmq';
    public const OPTION_SELENIUM_VERSION = 'selenium-version';
    public const OPTION_SELENIUM_IMAGE = 'selenium-image';

    /**
     * State modifiers.
     */
    public const OPTION_NODE = 'node';
    public const OPTION_MODE = 'mode';
    public const OPTION_SYNC_ENGINE = 'sync-engine';
    public const OPTION_NO_CRON = 'no-cron';
    public const OPTION_NO_VARNISH = 'no-varnish';
    public const OPTION_WITH_SELENIUM = 'with-selenium';
    public const OPTION_NO_TMP_MOUNTS = 'no-tmp-mounts';

    /**
     * Option key to config name map.
     *
     * @var array
     */
    private static $optionsMap = [
        self::OPTION_PHP => ServiceInterface::NAME_PHP,
        self::OPTION_DB => ServiceInterface::NAME_DB,
        self::OPTION_NGINX => ServiceInterface::NAME_NGINX,
        self::OPTION_REDIS => ServiceInterface::NAME_REDIS,
        self::OPTION_ES => ServiceInterface::NAME_ELASTICSEARCH,
        self::OPTION_NODE => ServiceInterface::NAME_NODE,
        self::OPTION_RABBIT_MQ => ServiceInterface::NAME_RABBITMQ,
        self::OPTION_SELENIUM_VERSION => ServiceFactory::SERVICE_SELENIUM_VERSION,
        self::OPTION_SELENIUM_IMAGE => ServiceFactory::SERVICE_SELENIUM_IMAGE
    ];

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @param InputInterface $input
     */
    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * @inheritDoc
     */
    public function read(): Repository
    {
        $repository = new Repository();

        $type = $this->input->getOption(self::OPTION_MODE);
        $syncEngine = $this->input->getOption(self::OPTION_SYNC_ENGINE);

        if (BuilderFactory::BUILDER_DEVELOPER === $type
            && !in_array($syncEngine, DeveloperBuilder::SYNC_ENGINES_LIST, true)
        ) {
            throw new ReaderException(sprintf(
                "File sync engine '%s' is not supported. Available: %s",
                $syncEngine,
                implode(', ', DeveloperBuilder::SYNC_ENGINES_LIST)
            ));
        }


        foreach (self::$optionsMap as $key => $option) {
            if ($value = $this->input->getOption($key)) {
                $repository->set($option, $value);
            }
        };

        $repository->set([
            self::OPTION_SYNC_ENGINE => $syncEngine,
            self::OPTION_NO_CRON => $this->input->getOption(self::OPTION_NO_CRON),
            self::OPTION_NO_VARNISH => $this->input->getOption(self::OPTION_NO_VARNISH),
            self::OPTION_WITH_SELENIUM => $this->input->getOption(self::OPTION_WITH_SELENIUM),
            self::OPTION_NO_TMP_MOUNTS => $this->input->getOption(self::OPTION_NO_TMP_MOUNTS),
        ]);

        return new Repository();
    }
}
