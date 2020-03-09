<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Dist;

use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;
use Magento\CloudDocker\Config\Environment\Shared\Reader as EnvReader;
use Magento\CloudDocker\Filesystem\FilesystemException;
use Magento\CloudDocker\Config\EnvCoder;

/**
 * Creates docker/config.php.dist file
 */
class Generator
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Formatter
     */
    private $phpFormatter;

    /**
     * @var Relationship
     */
    private $relationship;

    /**
     * @var EnvReader
     */
    private $envReader;

    /**
     * @var EnvCoder
     */
    private $envCoder;

    /**
     * @var array
     */
    private static $baseConfig = [
        'MAGENTO_CLOUD_ROUTES' => [
            'http://magento2.docker/' => [
                'type' => 'upstream',
                'original_url' => 'http://{default}'
            ],
            'https://magento2.docker/' => [
                'type' => 'upstream',
                'original_url' => 'https://{default}'
            ],
        ],
        'MAGENTO_CLOUD_VARIABLES' => [
            'ADMIN_EMAIL' => 'admin@example.com',
            'ADMIN_PASSWORD' => '123123q',
            'ADMIN_URL' => 'admin'
        ],
    ];

    /**
     * @param DirectoryList $directoryList
     * @param Filesystem $filesystem
     * @param Relationship $relationship
     * @param Formatter $phpFormatter
     * @param EnvReader $envReader
     * @param EnvCoder $envCoder
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        Relationship $relationship,
        Formatter $phpFormatter,
        EnvReader $envReader,
        EnvCoder $envCoder
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->phpFormatter = $phpFormatter;
        $this->relationship = $relationship;
        $this->envReader = $envReader;
        $this->envCoder = $envCoder;
    }

    /**
     * Create docker/config.php.dist and docker/config.env files
     * generate MAGENTO_CLOUD_RELATIONSHIPS according to services enablements.
     *
     * @param Config $config
     * @throws ConfigurationMismatchException
     * @throws FilesystemException
     */
    public function generate(Config $config): void
    {
        $dockerRootPath = $this->directoryList->getDockerRoot();
        $configByServices = $this->generateByServices($config);

        $this->saveConfigDist($dockerRootPath . '/config.php.dist', $configByServices);
        $this->saveConfigEnv(
            $dockerRootPath . '/config.env',
            array_merge(
                $configByServices,
                $this->envReader->read(),
                $config->getVariables()
            )
        );
    }

    /**
     * Generates MAGENTO_CLOUD_RELATIONSHIPS with credentials depends on enabled services.
     *
     * @param Config $config
     * @return array
     * @throws ConfigurationMismatchException
     */
    private function generateByServices(Config $config): array
    {
        return array_merge(
            ['MAGENTO_CLOUD_RELATIONSHIPS' => $this->relationship->get($config)],
            self::$baseConfig
        );
    }

    /**
     * Formats and save configuration to config.php.dist file.
     *
     * @param string $filePath
     * @param array $config
     */
    private function saveConfigDist(string $filePath, array $config): void
    {
        $result = "<?php\n\nreturn [";
        foreach ($config as $key => $value) {
            $result .= "\n    '{$key}' => ";
            $result .= 'base64_encode(json_encode(' . $this->phpFormatter->varExport($value, 2) . ')),';
        }
        $result .= "\n];\n";

        $this->filesystem->put($filePath, $result);
    }

    /**
     * Encodes needed variables and saves them to config.env file.
     *
     * @param string $filePath
     * @param array $config
     */
    private function saveConfigEnv(string $filePath, array $config): void
    {
        $result = '';
        foreach ($this->envCoder->encode($config) as $key => $value) {
            $result .= $key . '=' . $value . PHP_EOL;
        }

        $this->filesystem->put($filePath, $result);
    }
}
