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
use Magento\CloudDocker\Config\Config;
use Magento\CloudDocker\Config\ConfigFactory;
use Magento\CloudDocker\Config\Dist\Generator;
use Magento\CloudDocker\Config\Reader;
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
     * @var Config
     */
    private $config;

    /**
     * @var Reader\CloudReader
     */
    private $cloudReader;

    /**
     * @param BuilderFactory $composeFactory
     * @param Generator $distGenerator
     * @param ConfigFactory $configFactory
     * @param Filesystem $filesystem
     * @param Reader\CloudReader $cloudReader
     */
    public function __construct(
        BuilderFactory $composeFactory,
        Generator $distGenerator,
        ConfigFactory $configFactory,
        Filesystem $filesystem,
        Reader\CloudReader $cloudReader
    ) {
        $this->builderFactory = $composeFactory;
        $this->distGenerator = $distGenerator;
        $this->configFactory = $configFactory;
        $this->filesystem = $filesystem;
        $this->cloudReader = $cloudReader;

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
                Reader\CliReader::OPTION_PHP,
                null,
                InputOption::VALUE_REQUIRED,
                'PHP version'
            )
            ->addOption(
                Reader\CliReader::OPTION_NGINX,
                null,
                InputOption::VALUE_REQUIRED,
                'Nginx version'
            )
            ->addOption(
                Reader\CliReader::OPTION_DB,
                null,
                InputOption::VALUE_REQUIRED,
                'DB version'
            )
            ->addOption(
                Reader\CliReader::OPTION_EXPOSE_DB_PORT,
                null,
                InputOption::VALUE_REQUIRED,
                'Expose DB port'
            )
            ->addOption(
                Reader\CliReader::OPTION_REDIS,
                null,
                InputOption::VALUE_REQUIRED,
                'Redis version'
            )
            ->addOption(
                Reader\CliReader::OPTION_ES,
                null,
                InputOption::VALUE_REQUIRED,
                'Elasticsearch version'
            )
            ->addOption(
                Reader\CliReader::OPTION_RABBIT_MQ,
                null,
                InputOption::VALUE_REQUIRED,
                'RabbitMQ version'
            )
            ->addOption(
                Reader\CliReader::OPTION_NODE,
                null,
                InputOption::VALUE_REQUIRED,
                'Node.js version'
            )
            ->addOption(
                Reader\CliReader::OPTION_SELENIUM_VERSION,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium version'
            )
            ->addOption(
                Reader\CliReader::OPTION_SELENIUM_IMAGE,
                null,
                InputOption::VALUE_REQUIRED,
                'Selenium image'
            );

        $this->addOption(
            Reader\CliReader::OPTION_MODE,
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
                Reader\CliReader::OPTION_SYNC_ENGINE,
                null,
                InputOption::VALUE_REQUIRED,
                sprintf(
                    'File sync engine. Works only with developer mode. Available: (%s)',
                    implode(', ', DeveloperBuilder::SYNC_ENGINES_LIST)
                ),
                DeveloperBuilder::SYNC_ENGINE_NATIVE
            )
            ->addOption(
                Reader\CliReader::OPTION_NO_CRON,
                null,
                InputOption::VALUE_NONE,
                'Remove cron container'
            )
            ->addOption(
                Reader\CliReader::OPTION_NO_VARNISH,
                null,
                InputOption::VALUE_NONE,
                'Remove Varnish container'
            )
            ->addOption(
                Reader\CliReader::OPTION_WITH_SELENIUM,
                null,
                InputOption::VALUE_NONE
            )
            ->addOption(
                Reader\CliReader::OPTION_NO_TMP_MOUNTS,
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

        $builder = $this->builderFactory->create(
            $input->getOption(Reader\CliReader::OPTION_MODE)
        );
        $config = $this->configFactory->create([
            $this->cloudReader,
            new Reader\CliReader($input)
        ]);
        die(var_dump($config->all()));
        if (in_array(
            $input->getOption(Reader\CliReader::OPTION_MODE),
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
