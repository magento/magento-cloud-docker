<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Service\ServiceInterface;

interface BuilderInterface
{
    public const DIR_MAGENTO = '/app';

    public const SERVICE_GENERIC = 'generic';
    public const SERVICE_DB = 'db';
    public const SERVICE_DB_QUOTE = 'db-quote';
    public const SERVICE_DB_SALES = 'db-sales';
    public const SERVICE_FPM = 'fpm';
    public const SERVICE_FPM_XDEBUG = 'fpm_xdebug';
    public const SERVICE_BUILD = 'build';
    public const SERVICE_DEPLOY = 'deploy';
    public const SERVICE_WEB = 'web';
    public const SERVICE_VARNISH = 'varnish';
    public const SERVICE_SELENIUM = 'selenium';
    public const SERVICE_TLS = 'tls';
    public const SERVICE_RABBITMQ = ServiceInterface::SERVICE_RABBITMQ;
    public const SERVICE_REDIS = ServiceInterface::SERVICE_REDIS;
    public const SERVICE_ELASTICSEARCH = ServiceInterface::SERVICE_ELASTICSEARCH;
    public const SERVICE_NODE = 'node';
    public const SERVICE_CRON = 'cron';
    public const SERVICE_TEST = 'test';
    public const SERVICE_HEALTHCHECK = 'healthcheck';
    public const SERVICE_MAILHOG = 'mailhog';

    public const NETWORK_MAGENTO = 'magento';
    public const NETWORK_MAGENTO_BUILD = 'magento-build';

    public const VOLUME_MAGENTO = '.';
    public const VOLUME_DOCKER_MNT = '.docker/mnt';
    public const VOLUME_MARIADB_CONF = '.docker/mysql/mariadb.conf.d';
    public const VOLUME_MAGENTO_VENDOR = 'magento-vendor';
    public const VOLUME_MAGENTO_GENERATED = 'magento-generated';
    public const VOLUME_MAGENTO_DB = 'magento-db';
    public const VOLUME_MAGENTO_DB_QUOTE = 'magento-db-quote';
    public const VOLUME_MAGENTO_DB_SALES = 'magento-db-sales';
    public const VOLUME_MAGENTO_DEV = './dev';
    public const VOLUME_DOCKER_ETRYPOINT = '.docker/mysql/docker-entrypoint-initdb.d';
    public const VOLUME_DOCKER_ETRYPOINT_QUOTE = '.docker/mysql-quote/docker-entrypoint-initdb.d';
    public const VOLUME_DOCKER_ETRYPOINT_SALES = '.docker/mysql-sales/docker-entrypoint-initdb.d';

    public const SYNC_ENGINE_NATIVE = 'native';

    /**
     * @param Config $config
     * @return Manager
     *
     * @throws ConfigurationMismatchException
     */
    public function build(Config $config): Manager;

    /**
     * @return string
     */
    public function getPath(): string;
}
