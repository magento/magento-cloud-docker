<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Compose;

use Illuminate\Contracts\Config\Repository;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Service\ServiceInterface;
use Magento\CloudDocker\Service\ServiceFactory;

/**
 * Docker functional test builder.
 *
 * @codeCoverageIgnore
 */
class FunctionalCompose extends ProductionCompose
{
    const DIR_MAGENTO = '/app';
    const CRON_ENABLED = false;

    /**
     * @inheritDoc
     */
    public function build(Repository $config): array
    {
        $compose = parent::build($config);
        $compose['services']['generic']['env_file'] = [
            './.docker/composer.env',
            './.docker/global.env'
        ];
        $compose['services']['db']['ports'] = ['3306:3306'];
        $compose['volumes']['magento'] = [];
        $compose['volumes']['magento-build-var'] = [];
        $compose['volumes']['magento-build-etc'] = [];
        $compose['volumes']['magento-build-static'] = [];
        $compose['volumes']['magento-build-media'] = [];

        return $compose;
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(Repository $config, bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            '.:/ece-tools',
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
            'magento-var:' . self::DIR_MAGENTO . '/var:delegated',
            'magento-etc:' . self::DIR_MAGENTO . '/app/etc:delegated',
            'magento-static:' . self::DIR_MAGENTO . '/pub/static:delegated',
            'magento-media:' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoBuildVolumes(Repository $config, bool $isReadOnly): array
    {
        $flag = $isReadOnly ? ':ro' : ':rw';

        return [
            '.:/ece-tools',
            'magento:' . self::DIR_MAGENTO . $flag,
            'magento-vendor:' . self::DIR_MAGENTO . '/vendor' . $flag,
            'magento-generated:' . self::DIR_MAGENTO . '/generated' . $flag,
            'magento-build-var:' . self::DIR_MAGENTO . '/var:delegated',
            'magento-build-etc:' . self::DIR_MAGENTO . '/app/etc:delegated',
            'magento-build-static:' . self::DIR_MAGENTO . '/pub/static:delegated',
            'magento-build-media:' . self::DIR_MAGENTO . '/pub/media:delegated',
        ];
    }

    /**
     * @inheritDoc
     */
    protected function getVariables(): array
    {
        return [];
    }

    /**
     * @inheritDoc
     */
    protected function getServiceVersion(string $serviceName)
    {
        $mapDefaultVersion = [
            ServiceInterface::NAME_DB => '10.2',
            ServiceInterface::NAME_PHP => '7.2',
            ServiceInterface::NAME_NGINX => self::DEFAULT_NGINX_VERSION,
            ServiceInterface::NAME_VARNISH => self::DEFAULT_VARNISH_VERSION,
            ServiceInterface::NAME_ELASTICSEARCH => null,
            ServiceInterface::NAME_NODE => null,
            ServiceInterface::NAME_RABBITMQ => null,
            ServiceInterface::NAME_REDIS => null,
        ];

        if (!array_key_exists($serviceName, $mapDefaultVersion)) {
            throw new ConfigurationMismatchException(sprintf('Type "%s" is not supported', $serviceName));
        }

        return $mapDefaultVersion[$serviceName];
    }

    /**
     * @inheritDoc
     */
    protected function getPhpVersion(): string
    {
        return $this->getServiceVersion(ServiceInterface::NAME_PHP);
    }

    /**
     * @inheritDoc
     */
    public function getPath(): string
    {
        return $this->fileList->getEceToolsCompose();
    }

    /**
     * @inheritDoc
     */
    protected function getPhpExtensions(string $phpVersion): array
    {
        return array_unique(array_merge(
            ExtensionResolver::DEFAULT_PHP_EXTENSIONS,
            ['xsl', 'redis'],
            in_array($phpVersion, ['7.0', '7.1']) ? ['mcrypt'] : []
        ));
    }

    /**
     * @inheritDoc
     */
    protected function getDockerMount(): array
    {
        return [];
    }
}
