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
    public const NAME_PHP = 'php';
    public const NAME_DB = 'mysql';
    public const NAME_NGINX = 'nginx';
    public const NAME_REDIS = 'redis';
    public const NAME_ELASTICSEARCH = 'elasticsearch';
    public const NAME_RABBITMQ = 'rabbitmq';
    public const NAME_NODE = 'node';
    public const NAME_VARNISH = 'varnish';
    public const NAME_SELENIUM = 'selenium';
}
