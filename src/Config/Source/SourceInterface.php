<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * The generic source interface
 */
interface SourceInterface
{
    public const DIR_MAGENTO = '/app';

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

    /**
     * TLS
     */
    public const SERVICES_TLS = self::SERVICES . '.' . ServiceInterface::SERVICE_TLS;

    /**
     * DB
     */
    public const SERVICES_DB = self::SERVICES . '.' . ServiceInterface::SERVICE_DB;

    /**
     * Nginx
     */
    public const SERVICES_NGINX = self::SERVICES . '.' . ServiceInterface::SERVICE_NGINX;

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

    /**
     * PHP Xdebug
     */
    public const SERVICES_XDEBUG = self::SERVICES . '.' . ServiceInterface::SERVICE_FPM_XDEBUG;

    public const CRON = self::SERVICES . '.cron';
    public const CRON_JOBS = self::CRON . '.jobs';
    public const CRON_ENABLED = self::CRON . '.enabled';

    public const PHP = self::SERVICES . '.' . ServiceInterface::SERVICE_PHP;
    public const PHP_VERSION = self::PHP . '.version';
    public const PHP_ENABLED = self::PHP . '.enabled';
    public const PHP_ENABLED_EXTENSIONS = self::PHP . '.extensions.enabled';
    public const PHP_DISABLED_EXTENSIONS = self::PHP . '.extensions.disabled';

    /**
     * Config
     */
    public const SYSTEM_SYNC_ENGINE = 'system.sync_engine';
    public const SYSTEM_TMP_MOUNTS = 'system.tmp_mounts';
    public const SYSTEM_MODE = 'system.mode';
    public const CONFIG_HOST = 'config.host';
    public const CONFIG_PORT = 'config.port';
    public const SYSTEM_EXPOSE_DB_PORTS = 'system.expose_db_ports';

    public const VARIABLES = 'variables';

    /**
     * @return Repository
     *
     * @throws SourceException
     */
    public function read(): Repository;
}
