<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Service\ServiceInterface;

interface SourceInterface
{
    public const MOUNTS = 'mounts';

    /**
     * Services
     */
    public const SERVICES = 'services';

    /**
     * Selenium
     */
    public const SERVICES_SELENIUM = self::SERVICES . '.' . ServiceInterface::SERVICE_SELENIUM;
    public const SERVICES_SELENIUM_VERSION = self::SERVICES_SELENIUM . '.version';
    public const SERVICES_SELENIUM_IMAGE = self::SERVICES_SELENIUM . '.image';
    public const SERVICES_SELENIUM_ENABLED = self::SERVICES_SELENIUM . '.enabled';

    /**
     * Varnish
     */
    public const SERVICES_VARNISH = self::SERVICES . '.' . ServiceInterface::SERVICE_VARNISH;
    public const SERVICES_VARNISH_ENABLED = self::SERVICES_VARNISH . '.enabled';
    public const SERVICES_VARNISH_IMAGE = self::SERVICES_VARNISH . '.image';
    public const SERVICES_VARNISH_VERSION = self::SERVICES_VARNISH . '.version';

    /**
     * TLS
     */
    public const SERVICES_TLS = self::SERVICES . '.' . ServiceInterface::SERVICE_TLS;
    public const SERVICES_TLS_ENABLED = self::SERVICES_TLS . '.enabled';
    public const SERVICES_TLS_VERSION = self::SERVICES_TLS . '.version';
    public const SERVICES_TLS_IMAGE = self::SERVICES_TLS . '.image';

    /**
     * DB
     */
    public const SERVICES_DB = self::SERVICES . '.' . ServiceInterface::SERVICE_DB;

    /**
     * Nginx
     */
    public const SERVICES_NGINX = self::SERVICES . '.' . ServiceInterface::SERVICE_NGINX;
    public const SERVICES_NGINX_ENABLED = self::SERVICES_NGINX . '.enabled';
    public const SERVICES_NGINX_VERSION = self::SERVICES_NGINX . '.version';
    public const SERVICES_NGINX_IMAGE = self::SERVICES_NGINX . '.image';

    /**
     * Redis
     */
    public const SERVICES_REDIS = self::SERVICES . '.' . ServiceInterface::SERVICE_REDIS;

    /**
     * ES
     */
    public const SERVICES_ES = self::SERVICES . '.' . ServiceInterface::SERVICE_ELASTICSEARCH;

    /**
     * Node
     */
    public const SERVICES_NODE = self::SERVICES . '.' . ServiceInterface::SERVICE_NODE;

    /**
     * Rabbit MQ
     */
    public const SERVICES_RMQ = self::SERVICES . '.' . ServiceInterface::SERVICE_RABBITMQ;

    public const CRON = 'cron';
    public const CRON_JOBS = self::CRON . '.jobs';
    public const CRON_ENABLED = self::CRON . '.enabled';

    public const PHP = self::SERVICES . '.' . ServiceInterface::SERVICE_PHP;
    public const PHP_VERSION = self::PHP . '.version';
    public const PHP_ENABLED = self::PHP . '.enabled';
    public const PHP_EXTENSIONS = self::PHP . '.extensions';
    public const PHP_DISABLED_EXTENSIONS = self::PHP . '.disabled_extensions';

    /**
     * Config
     */
    public const CONFIG_SYNC_ENGINE = 'config.sync_engine';
    public const CONFIG_TMP_MOUNTS = 'config.tmp_mounts';
    public const CONFIG_MODE = 'config.mode';

    /**
     * @return Repository
     *
     * @throws SourceException
     */
    public function read(): Repository;
}
