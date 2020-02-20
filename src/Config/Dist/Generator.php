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
     */
    public function __construct(
        DirectoryList $directoryList,
        Filesystem $filesystem,
        Relationship $relationship,
        Formatter $phpFormatter
    ) {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
        $this->phpFormatter = $phpFormatter;
        $this->relationship = $relationship;
    }

    /**
     * Create docker/config.php.dist file
     * generate MAGENTO_CLOUD_RELATIONSHIPS according to services enablements.
     *
     * @param Config $config
     * @throws ConfigurationMismatchException
     */
    public function generate(Config $config): void
    {
        $configPath = $this->directoryList->getDockerRoot() . '/config.php.dist';

        $this->saveConfig(
            $configPath,
            array_merge(['MAGENTO_CLOUD_RELATIONSHIPS' => $this->relationship->get($config)], self::$baseConfig)
        );
    }

    /**
     * Formats and save configuration to file.
     *
     * @param string $filePath
     * @param array $config
     */
    private function saveConfig(string $filePath, array $config): void
    {
        $result = "<?php\n\nreturn [";
        foreach ($config as $key => $value) {
            $result .= "\n    '{$key}' => ";
            $result .= 'base64_encode(json_encode(' . $this->phpFormatter->varExport($value, 2) . ')),';
        }
        $result .= "\n];\n";

        $this->filesystem->put($filePath, $result);
    }
}
