<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command;

use Illuminate\Filesystem\Filesystem;
use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\Compose\DeveloperBuilder;
use Magento\CloudDocker\Compose\BuilderFactory;
use Magento\CloudDocker\Compose\ProductionBuilder;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Service\ServiceFactory;
use Magento\CloudDocker\Service\ServiceInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Builds Docker configuration for Magento project.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class BuildCompose extends Command
{
    public const NAME = 'build:compose';

    /**
     * Services.
     */
    private const OPTION_PHP = 'php';
    private const OPTION_NGINX = 'nginx';
    private const OPTION_DB = 'db';
    private const OPTION_EXPOSE_DB_PORT = 'expose-db-port';
    private const OPTION_REDIS = 'redis';
    private const OPTION_ES = 'es';
    private const OPTION_RABBIT_MQ = 'rmq';
    private const OPTION_SELENIUM_VERSION = 'selenium-version';
    private const OPTION_SELENIUM_IMAGE = 'selenium-image';

    /**
     * State modifiers.
     */
    private const OPTION_NODE = 'node';
    private const OPTION_MODE = 'mode';
    private const OPTION_SYNC_ENGINE = 'sync-engine';
    private const OPTION_NO_CRON = 'no-cron';
    private const OPTION_NO_VARNISH = 'no-varnish';
    private const OPTION_WITH_SELENIUM = 'with-selenium';

    /**
     * Option key to config name map.
     *
     * @var array
     */
    private static $optionsMap = [
        self::OPTION_PHP => ServiceInterface::NAME_PHP,
        self::OPTION_DB => ServiceInterface::NAME_DB,
        self::OPTION_NGINX => ServiceInterface::NAME_NGINX,
        self::OPTION_REDIS => ServiceInterface::NAME_REDIS,
        self::OPTION_ES => ServiceInterface::NAME_ELASTICSEARCH,
        self::OPTION_NODE => ServiceInterface::NAME_NODE,
        self::OPTION_RABBIT_MQ => ServiceInterface::NAME_RABBITMQ,
        self::OPTION_EXPOSE_DB_PORT => ProductionBuilder::KEY_EXPOSE_DB_PORT,
        self::OPTION_SELENIUM_VERSION => ServiceFactory::SERVICE_SELENIUM_VERSION,
        self::OPTION_SELENIUM_IMAGE => ServiceFactory::SERVICE_SELENIUM_IMAGE
    ];

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
     * @param BuilderFactory $composeFactory
     * @param Generator $distGenerator
     * @param ConfigFactory $configFactory
     * @param Filesystem $filesystem
     */
    public function __construct(
        BuilderFactory $composeFactory,
        Generator $distGenerator,
        ConfigFactory $configFactory,
        Filesystem $filesystem
    ) {
        $this->builderFactory = $composeFactory;
        $this->distGenerator = $distGenerator;
        $this->configFactory = $configFactory;
        $this->filesystem = $filesystem;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Build docker configuration')
            ->addOption(
                self::OPTION_PHP,
                null,
                InputOption::VALUE_REQUIRED,
                'PHP version'
            )
            ->addOption(
                self::OPTION_NGINX,
                null,
                InputOption::VALUE_REQUIRED,
                'Nginx version'
            )
            ->addOption(
                self::OPTION_DB,
                null,
                InputOption::VALUE_REQUIRED,
                'DB version'
            )
            ->addOption(
                self::OPTION_EXPOSE_DB_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'Expose DB port'
            )
            ->addOption(
                self::OPTION_REDIS,
                null,
                InputOption::VALUE_REQUIRED,
                'Redis version'
            )
            ->addOption(
                self::OPTION_ES,
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch version'
            )
            ->addOption(
                self::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_REQUIRED,
                'RabbitMQ version'
            )
            ->addOption(
                self::OPTION_NODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Node.js version'
            )
            ->addOption(
                self::OPTION_SELENIUM_VERSION,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium version'
            )
            ->addOption(
                self::OPTION_SELENIUM_IMAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium image'
            );

        $this->addOption(
            self::OPTION_MODE,
            'm',
            InputOption::VALUE_REQUIRED,
            sprintf(
                'Mode of environment (%s)',
                implode(
                    ', ',
                    [
                        BuilderFactory::BUILDER_DEVELOPER,
                        BuilderFactory::BUILDER_PRODUCTION,
                        BuilderFactory::BUILDER_FUNCTIONAL,
                    ]
                )
            ),
            BuilderFactory::BUILDER_PRODUCTION
        )
            ->addOption(
                self::OPTION_SYNC_ENGINE,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'File sync engine. Works only with developer mode. Available: (%s)',
                    implode(', ', DeveloperBuilder::SYNC_ENGINES_LIST)
                ),
                DeveloperBuilder::SYNC_ENGINE_NATIVE
            )
            ->addOption(
                self::OPTION_NO_CRON,
                null,
                InputOption::VALUE_NONE,
                'Remove cron container'
            )
            ->addOption(
                self::OPTION_NO_VARNISH,
                null,
                InputOption::VALUE_NONE,
                'Remove Varnish container'
            )
            ->addOption(
                self::OPTION_WITH_SELENIUM,
                null,
                InputOption::VALUE_NONE
            );

        parent::configure();
    }

    /**
     * {@inheritDoc}
     *
     * @throws GenericException
     */
    public function execute(InputInterface $input, OutputInterface $output)
    {
        $type = $input->getOption(self::OPTION_MODE);
        $syncEngine = $input->getOption(self::OPTION_SYNC_ENGINE);

        $builder = $this->builderFactory->create($type);
        $config = $this->configFactory->create();

        if (BuilderFactory::BUILDER_DEVELOPER === $type
            && !in_array($syncEngine, DeveloperBuilder::SYNC_ENGINES_LIST, true)
        ) {
            throw new GenericException(sprintf(
                "File sync engine '%s' is not supported. Available: %s",
                $syncEngine,
                implode(', ', DeveloperBuilder::SYNC_ENGINES_LIST)
            ));
        }

        array_walk(self::$optionsMap, static function ($key, $option) use ($config, $input) {
            if ($value = $input->getOption($option)) {
                $config->set($key, $value);
            }
        });

        $config->set([
            DeveloperBuilder::KEY_SYNC_ENGINE => $syncEngine,
            ProductionBuilder::KEY_NO_CRON => $input->getOption(self::OPTION_NO_CRON),
            ProductionBuilder::KEY_NO_VARNISH => $input->getOption(self::OPTION_NO_VARNISH),
            ProductionBuilder::KEY_WITH_SELENIUM => $input->getOption(self::OPTION_WITH_SELENIUM)
        ]);

        if (in_array(
            $input->getOption(self::OPTION_MODE),
            [BuilderFactory::BUILDER_DEVELOPER, BuilderFactory::BUILDER_PRODUCTION],
            false
        )) {
            $this->distGenerator->generate();
        }

        $compose = $builder->build($config);

        $this->filesystem->put(
            $builder->getPath(),
            Yaml::dump([
                'version' => $compose->getVersion(),
                'services' => $compose->getServices(),
                'volumes' => $compose->getVolumes(),
                'networks' => $compose->getNetworks()
            ], 6, 2, Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK)
        );

        $output->writeln('<info>Configuration was built.</info>');
    }
}
