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
    public const NAME = 'name';

    /**
     * Services
     */
    public const SERVICES = 'services';

    /**
     * Selenium
     */
    public const SERVICES_SELENIUM = self::SERVICES . '.' . ServiceInterface::SERVICE_SELENIUM;
    public const SERVICES_TEST = self::SERVICES . '.' . ServiceInterface::SERVICE_TEST;
    public const SERVICES_SELENIUM_VERSION = self::SERVICES_SELENIUM . '.version';
    public const SERVICES_SELENIUM_IMAGE = self::SERVICES_SELENIUM . '.image';
    public const SERVICES_SELENIUM_ENABLED = self::SERVICES_SELENIUM . '.enabled';
    public const SERVICES_TEST_ENABLED = self::SERVICES_TEST . '.enabled';

    /**
     * Varnish
     */
    public const SERVICES_VARNISH = self::SERVICES . '.' . ServiceInterface::SERVICE_VARNISH;
    public const SERVICES_VARNISH_ENABLED = self::SERVICES_VARNISH . '.enabled';

    /**
     * DB
     */
    public const SERVICES_DB = self::SERVICES . '.' . ServiceInterface::SERVICE_DB;
    public const SERVICES_DB_IMAGE = self::SERVICES_DB . '.image';

    /**
     * DB quote
     */
    public const SERVICES_DB_QUOTE = self::SERVICES . '.' . ServiceInterface::SERVICE_DB_QUOTE;

    /**
     * DB sales
     */
    public const SERVICES_DB_SALES = self::SERVICES . '.' . ServiceInterface::SERVICE_DB_SALES;

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
     * Mailhog
     */
    public const SERVICES_MAILHOG = self::SERVICES . '.' . ServiceInterface::SERVICE_MAILHOG;

    /**
     * ES environment variables
     */
    public const SERVICES_ES_VARS = self::SERVICES_ES . '.' . 'env-vars';

    /**
     * ES plugins
     */
    public const SERVICES_ES_PLUGINS = self::SERVICES_ES . '.configuration.plugins';

    /**
     * Node
     */
    public const SERVICES_NODE = self::SERVICES . '.' . ServiceInterface::SERVICE_NODE;

    /**
     * Rabbit MQ
     */
    public const SERVICES_RMQ = self::SERVICES . '.' . ServiceInterface::SERVICE_RABBITMQ;

    /**
     * Blackfire
     */
    public const SERVICES_BLACKFIRE = self::SERVICES . '.' . ServiceInterface::SERVICE_BLACKFIRE;
    public const SERVICES_BLACKFIRE_VERSION = self::SERVICES_BLACKFIRE . '.version';
    public const SERVICES_BLACKFIRE_IMAGE = self::SERVICES_BLACKFIRE . '.image';
    public const SERVICES_BLACKFIRE_ENABLED = self::SERVICES_BLACKFIRE . '.enabled';
    public const SERVICES_BLACKFIRE_CONFIG = self::SERVICES_BLACKFIRE . '.config';

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

    public const INSTALLATION_TYPE = 'install.type';

    public const MAGENTO_VERSION = 'magento.version';

    /**
     * Config
     */
    public const SYSTEM_SYNC_ENGINE = 'system.sync_engine';
    public const SYSTEM_TMP_MOUNTS = 'system.tmp_mounts';
    public const SYSTEM_MODE = 'system.mode';
    public const SYSTEM_HOST = 'system.host';
    public const SYSTEM_PORT = 'system.port';
    public const SYSTEM_TLS_PORT = 'system.tls_port';
    public const SYSTEM_EXPOSE_DB_PORTS = 'system.expose_db_ports';
    public const SYSTEM_EXPOSE_DB_QUOTE_PORTS = 'system.expose_db_quote_ports';
    public const SYSTEM_EXPOSE_DB_SALES_PORTS = 'system.expose_db_sales_ports';
    public const SYSTEM_DB_ENTRYPOINT = 'system.db_entrypoint';
    public const SYSTEM_MARIADB_CONF = 'system.mariadb_conf';
    public const SYSTEM_SET_DOCKER_HOST = 'system.set_docker_host';
    public const SYSTEM_MAILHOG_SMTP_PORT = 'system.mailhog.smtp_port';
    public const SYSTEM_MAILHOG_HTTP_PORT = 'system.mailhog.http_port';

    public const SYSTEM_DB_INCREMENT_INCREMENT = 'system.db.increment_increment';
    public const SYSTEM_DB_INCREMENT_OFFSET = 'system.db.increment_offset';

    public const VARIABLES = 'variables';

    public const HOOKS = 'hooks';

    /**
     * @return Repository
     *
     * @throws SourceException
     */
    public function read(): Repository;
}
