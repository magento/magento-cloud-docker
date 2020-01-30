<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Dist;

use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Config\Relationship;
use Magento\CloudDocker\Filesystem\DirectoryList;

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
     * @param array $cloudVariables
     * @param array $rawVariables
     * @throws ConfigurationMismatchException if can't obtain relationships
     */
    public function generate(array $cloudVariables = [], array $rawVariables = [])
    {
        $configPath = $this->directoryList->getDockerRoot() . '/config.php.dist';

        $config = array_merge(
            ['MAGENTO_CLOUD_RELATIONSHIPS' => $this->relationship->get()],
            self::$baseConfig
        );
        $config = array_replace($config, $cloudVariables);

        $this->saveConfig($configPath, $config, $rawVariables);
    }

    /**
     * Formats and save configuration to file.
     *
     * @param string $filePath
     * @param array $config
     * @param array $rawVariables
     */
    private function saveConfig(string $filePath, array $config, array $rawVariables)
    {
        $result = "<?php\n\nreturn [";
        foreach ($config as $key => $value) {
            $result .= "\n    '{$key}' => ";
            $result .= 'base64_encode(json_encode(' . $this->phpFormatter->varExport($value, 2) . ')),';
        }

        foreach ($rawVariables as $key => $value) {
            $result .= "\n    '{$key}' => '{$value}'";
        }

        $result .= "\n];\n";

        $this->filesystem->put($filePath, $result);
    }
}
