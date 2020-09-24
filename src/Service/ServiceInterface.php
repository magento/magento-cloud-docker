<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Service;

/**
 * Interface for installed services.
 *
 * @api
 */
interface ServiceInterface
{
    public const SERVICE_PHP = 'php';
    public const SERVICE_PHP_CLI = 'php-cli';
    public const SERVICE_PHP_FPM = 'php-fpm';
    public const SERVICE_FPM_XDEBUG = 'php-fpm-xdebug';
    public const SERVICE_DB = 'mysql';
    public const SERVICE_DB_QUOTE = 'mysql-quote';
    public const SERVICE_DB_SALES = 'mysql-sales';
    public const SERVICE_NGINX = 'nginx';
    public const SERVICE_REDIS = 'redis';
    public const SERVICE_ELASTICSEARCH = 'elasticsearch';
    public const SERVICE_RABBITMQ = 'rabbitmq';
    public const SERVICE_NODE = 'node';
    public const SERVICE_VARNISH = 'varnish';
    public const SERVICE_SELENIUM = 'selenium';
    public const SERVICE_TEST = 'test';
    public const SERVICE_TLS = 'tls';
    public const SERVICE_GENERIC = 'generic';
    public const SERVICE_BLACKFIRE = 'blackfire';
    public const SERVICE_MAILHOG = 'mailhog';
}
