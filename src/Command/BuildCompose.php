<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Compose\DeveloperBuilder;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Source;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArgvInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds Docker configuration for Magento project.
 *
 * @SuppressWarnings("PHPMD.CouplingBetweenObjects")
 */
class BuildCompose extends Command
{
    public const NAME = 'build:compose';

    /**
     * @var BuilderFactory
     */
    private $builderFactory;

    /**
     * @var Generator
     */
    private $distGenerator;

    /**
     * @var ConfigFactory
     */
    private $configFactory;

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var Source\SourceFactory
     */
    private $sourceFactory;

    /**
     * @param BuilderFactory $composeFactory
     * @param Generator $distGenerator
     * @param ConfigFactory $configFactory
     * @param Filesystem $filesystem
     * @param Source\SourceFactory $sourceFactory
     */
    public function __construct(
        BuilderFactory $composeFactory,
        Generator $distGenerator,
        ConfigFactory $configFactory,
        Filesystem $filesystem,
        Source\SourceFactory $sourceFactory
    ) {
        $this->builderFactory = $composeFactory;
        $this->distGenerator = $distGenerator;
        $this->configFactory = $configFactory;
        $this->filesystem = $filesystem;
        $this->sourceFactory = $sourceFactory;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings("PHPMD.ExcessiveMethodLength")
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Build docker configuration')
            ->addOption(
                Source\CliSource::OPTION_PHP,
                null,
                InputOption::VALUE_REQUIRED,
                'PHP version'
            )->addOption(
                Source\CliSource::OPTION_NGINX,
                null,
                InputOption::VALUE_REQUIRED,
                'Nginx version'
            )->addOption(
                Source\CliSource::OPTION_DB,
                null,
                InputOption::VALUE_REQUIRED,
                'DB version'
            )->addOption(
                Source\CliSource::OPTION_DB_IMAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'DB image'
            )->addOption(
                Source\CliSource::OPTION_EXPOSE_DB_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'Expose DB port'
            )->addOption(
                Source\CliSource::OPTION_EXPOSE_DB_QUOTE_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'Expose port for DB sales'
            )->addOption(
                Source\CliSource::OPTION_EXPOSE_DB_SALES_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'Expose port for DB quote'
            )->addOption(
                Source\CliSource::OPTION_WITH_ENTRYPOINT,
                null,
                InputOption::VALUE_NONE,
                'Add DB entrypoint volume'
            )->addOption(
                Source\CliSource::OPTION_WITH_MARIADB_CONF,
                null,
                InputOption::VALUE_NONE,
                'Add MariaDb config volume'
            )->addOption(
                Source\CliSource::OPTION_REDIS,
                null,
                InputOption::VALUE_REQUIRED,
                'Redis version'
            )->addOption(
                Source\CliSource::OPTION_ES,
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch version'
            )->addOption(
                Source\CliSource::OPTION_OS,
                null,
                InputOption::VALUE_REQUIRED,
                'Opensearch version'
            )->addOption(
                Source\CliSource::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_REQUIRED,
                'RabbitMQ version'
            )->addOption(
                Source\CliSource::OPTION_NODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Node.js version'
            )->addOption(
                Source\CliSource::OPTION_SELENIUM_VERSION,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium version'
            )->addOption(
                Source\CliSource::OPTION_SELENIUM_IMAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium image'
            )->addOption(
                Source\CliSource::OPTION_ZOOKEEPER_VERSION,
                null,
                InputOption::VALUE_REQUIRED,
                'Zookeeper version'
            )->addOption(
                Source\CliSource::OPTION_ZOOKEEPER_IMAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Zookeeper image'
            )->addOption(
                Source\CliSource::OPTION_NO_ES,
                null,
                InputOption::VALUE_NONE,
                'Disable Elasticsearch'
            )->addOption(
                Source\CliSource::OPTION_NO_OS,
                null,
                InputOption::VALUE_NONE,
                'Disable Opensearch'
            )->addOption(
                Source\CliSource::OPTION_NO_MAILHOG,
                null,
                InputOption::VALUE_NONE,
                'Disable mailhog'
            )->addOption(
                Source\CliSource::OPTION_MAILHOG_SMTP_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'MailHog SMTP port'
            )->addOption(
                Source\CliSource::OPTION_MAILHOG_HTTP_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'MailHog HTTP port'
            )->addOption(
                Source\CliSource::OPTION_SET_DOCKER_HOST_XDEBUG,
                null,
                InputOption::VALUE_NONE,
                'Deprecated option to resolve host.docker.internal on Linux. Did nothing at the moment'
            )->addOption(
                Source\CliSource::OPTION_NGINX_WORKER_PROCESSES,
                null,
                InputOption::VALUE_OPTIONAL,
                'The number of NGINX worker processes',
                Source\BaseSource::DEFAULT_NGINX_WORKER_PROCESSES
            )->addOption(
                Source\CliSource::OPTION_NGINX_WORKER_CONNECTIONS,
                null,
                InputOption::VALUE_OPTIONAL,
                'The maximum number of connections that each worker process can handle simultaneously',
                Source\BaseSource::DEFAULT_NGINX_WORKER_CONNECTIONS
            )->addOption(
                Source\CliSource::OPTION_CUSTOM_REGISTRY,
                null,
                InputOption::VALUE_OPTIONAL,
                'The custom registry. It can be your mirror with all needed images. For example: 123.example.com'
            );

        $this->addOption(
            Source\CliSource::OPTION_MODE,
            'm',
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Mode of environment (%s)',
                implode(
                    ', ',
                    [
                        BuilderFactory::BUILDER_DEVELOPER,
                        BuilderFactory::BUILDER_PRODUCTION,
                    ]
                )
            )
        )->addOption(
            Source\CliSource::OPTION_SYNC_ENGINE,
            null,
            InputOption::VALUE_REQUIRED,
            sprintf(
                'File sync engine. Works only with developer mode. Available: (%s)',
                implode(', ', DeveloperBuilder::SYNC_ENGINES_LIST)
            )
        )->addOption(
            Source\CliSource::OPTION_WITH_CRON,
            null,
            InputOption::VALUE_NONE,
            'Add cron container'
        )->addOption(
            Source\CliSource::OPTION_NO_VARNISH,
            null,
            InputOption::VALUE_NONE,
            'Remove Varnish container'
        )->addOption(
            Source\CliSource::OPTION_WITH_SELENIUM,
            null,
            InputOption::VALUE_NONE,
            'Add Selenium latest version'
        )->addOption(
            Source\CliSource::OPTION_WITH_ZOOKEEPER,
            null,
            InputOption::VALUE_NONE,
            'Add Zookeeper latest version'
        )->addOption(
            Source\CliSource::OPTION_WITH_TEST,
            null,
            InputOption::VALUE_NONE,
            'Add container for tests running'
        )->addOption(
            Source\CliSource::OPTION_NO_TMP_MOUNTS,
            null,
            InputOption::VALUE_NONE,
            'Remove /tmp mounted volume'
        )->addOption(
            Source\CliSource::OPTION_WITH_XDEBUG,
            null,
            InputOption::VALUE_NONE,
            'Enables XDebug'
        )->addOption(
            Source\CliSource::OPTION_ENV_VARIABLES,
            null,
            InputOption::VALUE_OPTIONAL,
            'Cloud environment variables'
        )->addOption(
            Source\CliSource::OPTION_INSTALLATION_TYPE,
            null,
            InputOption::VALUE_OPTIONAL,
            'Sets magento installation type',
            Source\BaseSource::INSTALLATION_TYPE_COMPOSER
        )->addOption(
            Source\CliSource::OPTION_HOST,
            null,
            InputOption::VALUE_REQUIRED,
            'Host name'
        )->addOption(
            Source\CliSource::OPTION_PORT,
            null,
            InputOption::VALUE_REQUIRED,
            'Port'
        )->addOption(
            Source\CliSource::OPTION_NO_TLS,
            null,
            InputOption::VALUE_NONE,
            'Disable TLS'
        )->addOption(
            Source\CliSource::OPTION_TLS_PORT,
            null,
            InputOption::VALUE_REQUIRED,
            'TLS port'
        )->addOption(
            Source\CliSource::OPTION_ES_ENVIRONMENT_VARIABLE,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Environment variable for elasticsearch service'
        )->addOption(
            Source\CliSource::OPTION_OS_ENVIRONMENT_VARIABLE,
            null,
            InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
            'Environment variable for opensearch service'
        )->addOption(
            Source\CliSource::OPTION_DB_INCREMENT_INCREMENT,
            null,
            InputOption::VALUE_REQUIRED,
            '"auto_increment_increment" database variable'
        )->addOption(
            Source\CliSource::OPTION_DB_INCREMENT_OFFSET,
            null,
            InputOption::VALUE_REQUIRED,
            '"auto_increment_offset" database variable'
        )->addOption(
            Source\CliSource::OPTION_ROOT_DIR,
            null,
            InputOption::VALUE_REQUIRED,
            'Root directory'
        );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws GenericException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
        $config = $this->configFactory->create([
            $this->sourceFactory->create(Source\BaseSource::class),
            $this->sourceFactory->create(Source\CloudBaseSource::class),
            $this->sourceFactory->create(Source\CloudSource::class),
            $this->sourceFactory->create(Source\ConfigSource::class),
            new Source\CliSource($input)
        ]);

        $builder = $this->builderFactory->create($config->getMode());
        $compose = $builder->build($config);

        $this->distGenerator->generate($config);

        if ($config->getSyncEngine() === DeveloperBuilder::SYNC_ENGINE_DOCKER_SYNC) {
            $output->writeln(
                '<comment>"Docker Sync" synchronization will be removed in next minor release.</comment>'
            );
        }

        $content = Yaml::dump([
            'version' => $compose->getVersion(),
            'services' => $compose->getServices(),
            'volumes' => $compose->getVolumes(),
            'networks' => $compose->getNetworks()
        ], 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK);

        if ($input instanceof ArgvInput) {
            $content = '# ./vendor/bin/ece-docker ' . $input . PHP_EOL . $content;
        }

        $this->filesystem->put(
            $builder->getPath(),
            $content
        );

        $output->writeln('<info>Configuration was built.</info>');

        return Cli::SUCCESS;
    }
}
