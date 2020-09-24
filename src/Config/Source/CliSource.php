<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;
use Symfony\Component\Console\Input\InputInterface;

/**
 * Source for CLI input
 */
class CliSource implements SourceInterface
{
    /**
     * Services.
     */
    public const OPTION_PHP = 'php';
    public const OPTION_NGINX = 'nginx';
    public const OPTION_DB = 'db';
    public const OPTION_DB_IMAGE = 'db-image';
    public const OPTION_EXPOSE_DB_PORT = 'expose-db-port';
    public const OPTION_EXPOSE_DB_QUOTE_PORT = 'expose-db-quote-port';
    public const OPTION_EXPOSE_DB_SALES_PORT = 'expose-db-sales-port';
    public const OPTION_REDIS = 'redis';
    public const OPTION_ES = 'es';
    public const OPTION_RABBIT_MQ = 'rmq';
    public const OPTION_SELENIUM_VERSION = 'selenium-version';
    public const OPTION_SELENIUM_IMAGE = 'selenium-image';
    public const OPTION_INSTALLATION_TYPE = 'installation-type';
    public const OPTION_NO_ES = 'no-es';
    public const OPTION_NO_MAILHOG = 'no-mailhog';

    /**
     * MailHog configuration
     */
    public const OPTION_MAILHOG_SMTP_PORT = 'mailhog-smtp-port';
    public const OPTION_MAILHOG_HTTP_PORT = 'mailhog-http-port';

    /**
     * State modifiers.
     */
    public const OPTION_NODE = 'node';
    public const OPTION_MODE = 'mode';
    public const OPTION_WITH_CRON = 'with-cron';
    public const OPTION_NO_VARNISH = 'no-varnish';
    public const OPTION_WITH_SELENIUM = 'with-selenium';
    public const OPTION_WITH_TEST = 'with-test';
    public const OPTION_NO_TMP_MOUNTS = 'no-tmp-mounts';
    public const OPTION_SYNC_ENGINE = 'sync-engine';
    public const OPTION_WITH_XDEBUG = 'with-xdebug';
    public const OPTION_SET_DOCKER_HOST_XDEBUG = 'set-docker-host';
    public const OPTION_WITH_ENTRYPOINT = 'with-entrypoint';
    public const OPTION_WITH_MARIADB_CONF = 'with-mariadb-conf';

    /**
     * Environment variables.
     */
    public const OPTION_ENV_VARIABLES = 'env-vars';

    /**
     * Host configuration
     */
    public const OPTION_HOST = 'host';
    public const OPTION_PORT = 'port';
    public const OPTION_TLS_PORT = 'tls-port';

    public const OPTION_DB_INCREMENT_INCREMENT = 'db-increment-increment';
    public const OPTION_DB_INCREMENT_OFFSET = 'db-increment-offset';

    /**
     * Environment variable for elasticsearch service.
     */
    public const OPTION_ES_ENVIRONMENT_VARIABLE = 'es-env-var';

    /**
     * List of service enabling options
     *
     * @var array
     */
    private static $enableOptionsMap = [
        self::OPTION_PHP => [
            self::PHP => true
        ],
        self::OPTION_DB => [
            self::SERVICES_DB => true,
            self::SERVICES_DB_QUOTE => false,
            self::SERVICES_DB_SALES => false
        ],
        self::OPTION_NGINX => [
            self::SERVICES_NGINX => true
        ],
        self::OPTION_REDIS => [
            self::SERVICES_REDIS => true
        ],
        self::OPTION_ES => [
            self::SERVICES_ES => true
        ],
        self::OPTION_NODE => [
            self::SERVICES_NODE => true
        ],
        self::OPTION_RABBIT_MQ => [
            self::SERVICES_RMQ => true
        ],
    ];

    /**
     * List of service disabling options
     *
     * @var array
     */
    private static $disableOptionsMap = [
        self::OPTION_NO_ES => self::SERVICES_ES,
        self::OPTION_NO_MAILHOG => self::SERVICES_MAILHOG,
    ];

    /**
     * @var InputInterface
     */
    private $input;

    /**
     * @param InputInterface $input
     */
    public function __construct(InputInterface $input)
    {
        $this->input = $input;
    }

    /**
     * {@inheritDoc}
     *
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function read(): Repository
    {
        $repository = new Repository();

        if ($mode = $this->input->getOption(self::OPTION_MODE)) {
            $repository->set([
                self::SYSTEM_MODE => $mode
            ]);
        }

        if ($syncEngine = $this->input->getOption(self::OPTION_SYNC_ENGINE)) {
            $repository->set([
                self::SYSTEM_SYNC_ENGINE => $syncEngine,
            ]);
        }

        /**
         * Loop through options to enable services.
         * Each option may have one or more dependencies.
         *
         * The dependencies must be in sync.
         * The dependencies which does not change status, must keep their default status.
         */
        foreach (self::$enableOptionsMap as $option => $services) {
            if ($value = $this->input->getOption($option)) {
                foreach ($services as $service => $status) {
                    $repository->set([
                        $service . '.version' => $value
                    ]);

                    if ($status === true) {
                        $repository->set([
                            $service . '.enabled' => true
                        ]);
                    }
                }
            }
        }

        foreach (self::$disableOptionsMap as $option => $service) {
            if ($value = $this->input->getOption($option)) {
                $repository->set([
                    $service . '.enabled' => false
                ]);
            }
        }

        if ($this->input->getOption(self::OPTION_WITH_SELENIUM)) {
            $repository->set([
                self::SERVICES_SELENIUM_ENABLED => true
            ]);
        }

        if ($this->input->getOption(self::OPTION_WITH_TEST)) {
            $repository->set([
                self::SERVICES_TEST_ENABLED => true
            ]);
        }

        if ($seleniumImage = $this->input->getOption(self::OPTION_SELENIUM_IMAGE)) {
            $repository->set([
                self::SERVICES_SELENIUM_ENABLED => true,
                self::SERVICES_SELENIUM_IMAGE => $seleniumImage
            ]);
        }

        if ($seleniumVersion = $this->input->getOption(self::OPTION_SELENIUM_VERSION)) {
            $repository->set([
                self::SERVICES_SELENIUM_ENABLED => true,
                self::SERVICES_SELENIUM_VERSION => $seleniumVersion
            ]);
        }

        if ($dbImage = $this->input->getOption(self::OPTION_DB_IMAGE)) {
            $repository->set([
                self::SERVICES_DB_IMAGE => $dbImage
            ]);
        }

        if ($this->input->getOption(self::OPTION_NO_TMP_MOUNTS)) {
            $repository->set(self::SYSTEM_TMP_MOUNTS, false);
        }

        if ($this->input->getOption(self::OPTION_WITH_CRON)) {
            $repository->set(self::CRON_ENABLED, true);
        }

        if ($this->input->getOption(self::OPTION_NO_VARNISH)) {
            $repository->set(self::SERVICES_VARNISH_ENABLED, false);
        }

        if ($this->input->getOption(self::OPTION_WITH_XDEBUG)) {
            $repository->set([
                self::SERVICES_XDEBUG . '.enabled' => true
            ]);
        }

        if ($this->input->getOption(self::OPTION_SET_DOCKER_HOST_XDEBUG)) {
            $repository->set(self::SYSTEM_SET_DOCKER_HOST, true);
        }

        if ($envs = $this->input->getOption(self::OPTION_ENV_VARIABLES)) {
            $repository->set(self::VARIABLES, (array)json_decode($envs, true));
        }

        if ($dbPort = $this->input->getOption(self::OPTION_EXPOSE_DB_PORT)) {
            $repository->set(self::SYSTEM_EXPOSE_DB_PORTS, $dbPort);
        }

        if ($host = $this->input->getOption(self::OPTION_HOST)) {
            $repository->set(self::SYSTEM_HOST, $host);
        }

        if ($port = $this->input->getOption(self::OPTION_PORT)) {
            $repository->set(self::SYSTEM_PORT, $port);
        }

        if ($port = $this->input->getOption(self::OPTION_TLS_PORT)) {
            $repository->set(self::SYSTEM_TLS_PORT, $port);
        }

        if ($installationType = $this->input->getOption(self::OPTION_INSTALLATION_TYPE)) {
            $repository->set(self::INSTALLATION_TYPE, $installationType);
        }

        if ($port = $this->input->getOption(self::OPTION_EXPOSE_DB_QUOTE_PORT)) {
            $repository->set(self::SYSTEM_EXPOSE_DB_QUOTE_PORTS, $port);
        }

        if ($port = $this->input->getOption(self::OPTION_EXPOSE_DB_SALES_PORT)) {
            $repository->set(self::SYSTEM_EXPOSE_DB_SALES_PORTS, $port);
        }

        if ($esEnvVars = $this->input->getOption(self::OPTION_ES_ENVIRONMENT_VARIABLE)) {
            $repository->set(self::SERVICES_ES_VARS, $esEnvVars);
        }

        if ($incrementIncrement = $this->input->getOption(self::OPTION_DB_INCREMENT_INCREMENT)) {
            $repository->set(SourceInterface::SYSTEM_DB_INCREMENT_INCREMENT, $incrementIncrement);
        }

        if ($incrementOffset = $this->input->getOption(self::OPTION_DB_INCREMENT_OFFSET)) {
            $repository->set(SourceInterface::SYSTEM_DB_INCREMENT_OFFSET, $incrementOffset);
        }

        if ($this->input->getOption(self::OPTION_WITH_ENTRYPOINT)) {
            $repository->set(SourceInterface::SYSTEM_DB_ENTRYPOINT, true);
        }

        if ($this->input->getOption(self::OPTION_WITH_MARIADB_CONF)) {
            $repository->set(SourceInterface::SYSTEM_MARIADB_CONF, true);
        }

        if ($port = $this->input->getOption(self::OPTION_MAILHOG_SMTP_PORT)) {
            $repository->set(self::SYSTEM_MAILHOG_SMTP_PORT, $port);
        }

        if ($port = $this->input->getOption(self::OPTION_MAILHOG_HTTP_PORT)) {
            $repository->set(self::SYSTEM_MAILHOG_HTTP_PORT, $port);
        }

        return $repository;
    }
}
