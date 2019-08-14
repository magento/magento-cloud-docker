<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CloudDocker\Service;

/**
 * Interface for installed services.
 *
 * @api
 */
interface ServiceInterface
{
    const NAME_PHP = 'php';
    const NAME_DB = 'mysql';
    const NAME_NGINX = 'nginx';
    const NAME_REDIS = 'redis';
    const NAME_ELASTICSEARCH = 'elasticsearch';
    const NAME_RABBITMQ = 'rabbitmq';
    const NAME_NODE = 'node';
    const NAME_VARNISH = 'varnish';
}
