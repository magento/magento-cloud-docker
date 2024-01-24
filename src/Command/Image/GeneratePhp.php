<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Image;

use Composer\Semver\Semver;
use Magento\CloudDocker\App\ConfigurationMismatchException;
use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Compose\Php\ExtensionResolver;
use Magento\CloudDocker\Compose\Php\ConfigFilesResolver;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates PHP images.
 */
class GeneratePhp extends Command
{
    private const NAME = 'image:generate:php';
    private const SUPPORTED_VERSIONS = ['7.2', '7.3', '7.4', '8.0', '8.1', '8.2', '8.3'];

    private const VERSION_MAP = [
        '7.2' => '7.2',
        '7.3' => '7.3',
        '7.4' => '7.4',
        '8.0' => '8.0.14',
        '8.1' => '8.1.1',
        '8.2' => '8.2',
        '8.3' => '8.3'
    ];

    private const EDITION_CLI = 'cli';
    private const EDITION_FPM = 'fpm';
    private const EDITIONS = [self::EDITION_CLI, self::EDITION_FPM];

    private const ARGUMENT_VERSION = 'version';
    private const DEFAULT_PACKAGES_PHP_FPM = [
        'apt-utils',
        'sendmail-bin',
        'sendmail',
        'sudo',
        'iproute2',
        'git',
    ];
    private const DEFAULT_PACKAGES_PHP_CLI = [
        'apt-utils',
        'cron',
        'git',
        'mariadb-client',
        'nano',
        'nodejs',
        'python3',
        'python3-pip',
        'redis-tools',
        'sendmail-bin',
        'sendmail',
        'sudo',
        'unzip',
        'vim',
        'openssh-client',
        'jq'
    ];

    private const PHP_EXTENSIONS_ENABLED_BY_DEFAULT = [
        'bcmath',
        'bz2',
        'calendar',
        'exif',
        'gd',
        'gettext',
        'intl',
        'mysqli',
        'mcrypt',
        'pcntl',
        'pdo_mysql',
        'soap',
        'sockets',
        'sysvmsg',
        'sysvsem',
        'sysvshm',
        'redis',
        'opcache',
        'xsl',
        'zip',
        'sodium'
    ];

    private const DOCKERFILE = 'Dockerfile';

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Semver
     */
    private $semver;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Filesystem $filesystem
     * @param Semver $semver
     * @param DirectoryList $directoryList
     */
    public function __construct(Filesystem $filesystem, Semver $semver, DirectoryList $directoryList)
    {
        $this->filesystem = $filesystem;
        $this->semver = $semver;
        $this->directoryList = $directoryList;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Generates proper configs')
            ->addArgument(
                self::ARGUMENT_VERSION,
                InputArgument::OPTIONAL | InputArgument::IS_ARRAY,
                'Generates PHP configuration',
                self::SUPPORTED_VERSIONS
            );

        parent::configure();
    }

    /**
     * {@inheritdoc}
     *
     * @throws ConfigurationMismatchException
     * @throws FileNotFoundException
     * @throws \Magento\CloudDocker\Filesystem\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $versions = $input->getArgument(self::ARGUMENT_VERSION);

        if ($diff = array_diff($versions, self::SUPPORTED_VERSIONS)) {
            throw new ConfigurationMismatchException(sprintf(
                'Not supported versions %s',
                implode(' ', $diff)
            ));
        }

        foreach ($versions as $version) {
            foreach (self::EDITIONS as $edition) {
                $this->build($version, $edition);
            }
        }

        $output->writeln('<info>Done</info>');

        return Cli::SUCCESS;
    }

    /**
     * @param string $version
     * @param string $edition
     * @throws ConfigurationMismatchException
     * @throws FileNotFoundException
     * @throws \Magento\CloudDocker\Filesystem\FileSystemException
     */
    private function build(string $version, string $edition): void
    {
        $destination = $this->directoryList->getImagesRoot()
            . '/php/'
            . $version . '-' . $edition;
        $dataDir = $this->directoryList->getImagesRoot() . '/php/' . $edition;
        $dockerfile = $destination . '/' . self::DOCKERFILE;

        $this->filesystem->deleteDirectory($destination);
        $this->filesystem->makeDirectory($destination);
        $this->filesystem->copyDirectory($dataDir, $destination, null, false);

        foreach (ConfigFilesResolver::getFolders($edition) as $folder) {
            $this->filesystem->copyDirectory(
                $dataDir . '/' . $folder,
                $destination . '/' . $folder
            );
        }

        $this->filesystem->makeDirectory($destination . '/etc');
        foreach (ConfigFilesResolver::getConfigFiles($edition) as $config) {
            foreach ($config as $constraint => $paths) {
                if (!$this->semver::satisfies($version, $constraint)) {
                    continue;
                }

                $this->filesystem->copy(
                    $dataDir . '/' . $paths[ConfigFilesResolver::FROM_PATH],
                    $destination . '/' . $paths[ConfigFilesResolver::TO_PATH]
                );
            }
        }

        $this->filesystem->put($dockerfile, $this->buildDockerfile($dockerfile, $version, $edition));
    }

    /**
     * @param string $dockerfile
     * @param string $phpVersion
     * @param string $edition
     * @return string
     * @throws ConfigurationMismatchException
     * @throws FileNotFoundException
     *
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    private function buildDockerfile(string $dockerfile, string $phpVersion, string $edition): string
    {
        $phpExtConfigs = ExtensionResolver::getConfig();

        $packages = self::EDITION_CLI === $edition ? self::DEFAULT_PACKAGES_PHP_CLI : self::DEFAULT_PACKAGES_PHP_FPM;
        $phpExtCore = [];
        $phpExtCoreConfigOptions = [];
        $phpExtPecl = [];
        $phpExtInstScripts = [];
        $phpExtEnabledDefault = [];

        foreach ($phpExtConfigs as $phpExtName => $phpExtConfig) {
            if (!is_string($phpExtName)) {
                throw new ConfigurationMismatchException('Extension name not set');
            }
            foreach ($phpExtConfig as $phpExtConstraint => $phpExtInstallConfig) {
                if (!$this->semver::satisfies($phpVersion, $phpExtConstraint)) {
                    continue;
                }
                $phpExtType = $phpExtInstallConfig[ExtensionResolver::EXTENSION_TYPE];
                switch ($phpExtType) {
                    case ExtensionResolver::EXTENSION_TYPE_CORE:
                        $phpExtCore[] = $phpExtInstallConfig[ExtensionResolver::EXTENSION_PACKAGE_NAME] ?? $phpExtName;
                        if (isset($phpExtInstallConfig[ExtensionResolver::EXTENSION_CONFIGURE_OPTIONS])) {
                            $phpExtCoreConfigOptions[] = sprintf(
                                "RUN docker-php-ext-configure \\\n  %s %s",
                                $phpExtName,
                                implode(' ', $phpExtInstallConfig[ExtensionResolver::EXTENSION_CONFIGURE_OPTIONS])
                            );
                        }
                        break;
                    case ExtensionResolver::EXTENSION_TYPE_PECL:
                        $phpExtPecl[] = $phpExtInstallConfig[ExtensionResolver::EXTENSION_PACKAGE_NAME] ?? $phpExtName;
                        break;
                    case ExtensionResolver::EXTENSION_TYPE_INSTALLATION_SCRIPT:
                        $phpExtInstScripts[] = implode(" \\\n", array_map(static function (string $command) {
                            return strpos($command, 'RUN') === false ? '  && ' . $command : $command;
                        }, explode(
                            "\n",
                            'RUN ' . $phpExtInstallConfig[ExtensionResolver::EXTENSION_INSTALLATION_SCRIPT]
                        )));
                        break;
                    default:
                        throw new ConfigurationMismatchException(sprintf(
                            'PHP extension %s. The type %s not supported',
                            $phpExtName,
                            $phpExtType
                        ));
                }
                if (isset($phpExtInstallConfig[ExtensionResolver::EXTENSION_OS_DEPENDENCIES])) {
                    $packages = array_merge(
                        $packages,
                        $phpExtInstallConfig[ExtensionResolver::EXTENSION_OS_DEPENDENCIES]
                    );
                }

                if (in_array($phpExtName, self::PHP_EXTENSIONS_ENABLED_BY_DEFAULT, true)) {
                    $phpExtEnabledDefault[] = $phpExtName;
                }
            }
        }

        if ($this->semver::satisfies($phpVersion, '>=8.2')) {
            $packages[] = 'python3-yaml';
            $pythonPackages = '';
        } else {
            $pythonPackages = 'RUN pip3 install --upgrade setuptools && pip3 install pyyaml';
        }

        $volumes = [
            'root' => [
                'def' => 'VOLUME ${MAGENTO_ROOT}',
                'cmd' => 'RUN mkdir -p ${MAGENTO_ROOT}'
            ]
        ];
        $volumesCmd = '';
        $volumesDef = '';
        foreach ($volumes as $data) {
            $volumesCmd .= $data['cmd'] ? $data['cmd'] . "\n" : '';
            $volumesDef .= $data['def'] ? $data['def'] . "\n" : '';
        }

        return strtr(
            $this->filesystem->get($dockerfile),
            [
                '{%note%}' => '# This file is automatically generated. Do not edit directly. #',
                '{%version%}' => self::VERSION_MAP[$phpVersion],
                '{%composer_version%}' => $this->getComposerVersion($phpVersion),
                '{%packages%}' => implode(" \\\n  ", array_unique($packages)),
                '{%docker-php-ext-configure%}' => implode(PHP_EOL, $phpExtCoreConfigOptions),
                '{%docker-php-ext-install%}' => $phpExtCore
                    ? "RUN docker-php-ext-install -j$(nproc) \\\n  " . implode(" \\\n  ", $phpExtCore)
                    : '',
                '{%php-pecl-extensions%}' => $phpExtPecl
                    ? "RUN pecl install -o -f \\\n  " . implode(" \\\n  ", $phpExtPecl)
                    : '',
                '{%installation_scripts%}' => $phpExtInstScripts
                    ? implode(PHP_EOL, $phpExtInstScripts)
                    : '',
                '{%env_php_extensions%}' => $phpExtEnabledDefault
                    ? 'ENV PHP_EXTENSIONS ' . implode(' ', $phpExtEnabledDefault)
                    : '',
                '{%python_packages%}' => $pythonPackages,
                '{%volumes_cmd%}' => rtrim($volumesCmd),
                '{%volumes_def%}' => rtrim($volumesDef)
            ]
        );
    }

    /**
     * @param string $phpVersion
     * @return string
     */
    private function getComposerVersion(string $phpVersion) : string
    {
        if ($this->semver::satisfies($phpVersion, '<8.0')) {
            return '1.10.22';
        }

        return $this->semver::satisfies($phpVersion, '<8.2') ? '2.1.14' : '2.2.18';
    }
}
