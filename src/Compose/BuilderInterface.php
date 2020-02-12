<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Service\ServiceInterface;

interface BuilderInterface
{
    public const DIR_MAGENTO = '/app';

    public const DEFAULT_NGINX_VERSION = 'latest';
    public const DEFAULT_VARNISH_VERSION = 'latest';
    public const DEFAULT_TLS_VERSION = 'latest';

    public const KEY_NO_VARNISH = 'no-varnish';
    public const KEY_EXPOSE_DB_PORT = 'expose-db-port';
    public const KEY_NO_TMP_MOUNTS = 'no-tmp-mounts';
    public const KEY_WITH_SELENIUM = 'with-selenium';
    public const KEY_SYNC_ENGINE = 'sync-engine';
    public const KEY_WITH_CRON = 'with-cron';

    public const SERVICE_GENERIC = 'generic';
    public const SERVICE_DB = 'db';
    public const SERVICE_DB_QUOTE = 'db-quote';
    public const SERVICE_DB_SALES = 'db-sales';
    public const SERVICE_FPM = 'fpm';
    public const SERVICE_BUILD = 'build';
    public const SERVICE_DEPLOY = 'deploy';
    public const SERVICE_WEB = 'web';
    public const SERVICE_VARNISH = 'varnish';
    public const SERVICE_SELENIUM = 'selenium';
    public const SERVICE_TLS = 'tls';
    public const SERVICE_RABBITMQ = ServiceInterface::NAME_RABBITMQ;
    public const SERVICE_REDIS = ServiceInterface::NAME_REDIS;
    public const SERVICE_ELASTICSEARCH = ServiceInterface::NAME_ELASTICSEARCH;
    public const SERVICE_NODE = 'node';
    public const SERVICE_CRON = 'cron';
    public const SERVICE_TEST = 'test';

    public const NETWORK_MAGENTO = 'magento';
    public const NETWORK_MAGENTO_BUILD = 'magento-build';

    public const VOLUME_MAGENTO = 'magento';
    public const VOLUME_MAGENTO_VENDOR = 'magento-vendor';
    public const VOLUME_MAGENTO_GENERATED = 'magento-generated';
    public const VOLUME_MAGENTO_VAR = 'magento-var';
    public const VOLUME_MAGENTO_ETC = 'magento-etc';
    public const VOLUME_MAGENTO_STATIC = 'magento-static';
    public const VOLUME_MAGENTO_MEDIA = 'magento-media';
    public const VOLUME_MAGENTO_DB = 'magento-db';
    public const VOLUME_MAGENTO_DEV = 'magento-dev';
    public const VOLUME_DOCKER_MNT = 'docker-mnt';
    public const VOLUME_DOCKER_ETRYPOINT = 'docker-entrypoint';
    public const VOLUME_DOCKER_ETRYPOINT_QUOTE = 'docker-entrypoint-quote';
    public const VOLUME_DOCKER_ETRYPOINT_SALES = 'docker-entrypoint-sales';
    public const VOLUME_MARIADB_CONF = 'mariadb-conf';
    public const SYNC_ENGINE_NATIVE = 'native';

    /**
     * @param Repository $config
     * @return Manager
     *
     * @throws ConfigurationMismatchException
     */
    public function build(Repository $config): Manager;

    /**
     * @return string
     */
    public function getPath(): string;
}
