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
use Magento\CloudDocker\Config\Environment\Converter;
use Magento\CloudDocker\Config\Environment\Reader;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileList;
use Magento\CloudDocker\Service\Config;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;

/**
 * Docker functional test builder.
 *
 * @codeCoverageIgnore
 */
class FunctionalBuilder extends ProductionBuilder
{
    /**
     * @var FileList
     */
    private $fileList;

    /**
     * @param ServiceFactory $serviceFactory
     * @param Config $serviceConfig
     * @param FileList $fileList
     * @param DirectoryList $directoryList
     * @param Converter $converter
     * @param ExtensionResolver $phpExtension
     * @param Reader $reader
     */
    public function __construct(
        ServiceFactory $serviceFactory,
        Config $serviceConfig,
        FileList $fileList,
        DirectoryList $directoryList,
        Converter $converter,
        ExtensionResolver $phpExtension,
        Reader $reader
    ) {
        $this->fileList = $fileList;

        parent::__construct(
            $serviceFactory,
            $serviceConfig,
            $fileList,
            $directoryList,
            $converter,
            $phpExtension,
            $reader
        );
    }

    /**
     * @inheritDoc
     */
    public function build(): array
    {
        $compose = parent::build();
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
    public function setConfig(Repository $config): void
    {
        $config->set(self::KEY_WITH_CRON, true);

        parent::setConfig($config);
    }

    /**
     * @inheritDoc
     */
    protected function getMagentoVolumes(bool $isReadOnly): array
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
    protected function getMagentoBuildVolumes(bool $isReadOnly): array
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
    protected function getServiceVersion(string $serviceName): ?string
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
