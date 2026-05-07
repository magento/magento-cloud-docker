<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Image;

use Magento\CloudDocker\Cli;
use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\FileNotFoundException;
use Magento\CloudDocker\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generates es images from template
 */
class GenerateEs extends Command
{
    private const NAME = 'image:generate:es';

    private const SINGLE_NODE = 'RUN echo "discovery.type: single-node" >> '
    . '/usr/share/elasticsearch/config/elasticsearch.yml';

    /**
     * Configuration map for generating es images data
     *
     * @var array
     */
    private $versionMap = [
        '7.10' => [
            'real-version' => '7.10.2',
            'single-node' => true,
        ],
        '7.11' => [
            'real-version' => '7.11.2',
            'single-node' => true,
        ],
        '8' => [
            'real-version' => '8.11.3',
            'single-node' => true,
        ],
    ];

    /**
     * @var Filesystem
     */
    private $filesystem;

    /**
     * @var DirectoryList
     */
    private $directoryList;

    /**
     * @param Filesystem $filesystem
     * @param DirectoryList $directoryList
     */
    public function __construct(Filesystem $filesystem, DirectoryList $directoryList)
    {
        $this->filesystem = $filesystem;
        $this->directoryList = $directoryList;

        parent::__construct();
    }

    /**
     * @inheritdoc
     */
    protected function configure(): void
    {
        $this->setName(self::NAME)
            ->setDescription('Generates elasticsearch configs');

        parent::configure();
    }

    /**
     * Generates data for elasticsearch images.
     *
     * {@inheritDoc}
     * @throws FileNotFoundException
     * @throws \Magento\CloudDocker\Filesystem\FileSystemException
     */
    public function execute(InputInterface $input, OutputInterface $output): int
    {
// phpcs:disable
        $fixRepo = <<<FIX
sed -i 's/mirrorlist/#mirrorlist/g' /etc/yum.repos.d/CentOS-Linux-* && \
    sed -i 's|#baseurl=http://mirror.centos.org|baseurl=https://mirror.rackspace.com\/centos-vault|g' /etc/yum.repos.d/CentOS-Linux-* && \
    
FIX;
// phpcs:enable
        $log4jFix = <<<LOG4J
RUN %s yum -y install zip && \
    zip -q -d /usr/share/elasticsearch/lib/log4j-core-*.jar org/apache/logging/log4j/core/lookup/JndiLookup.class && \
    yum remove -y zip && \
    yum -y clean all && \
    rm -rf /var/cache

LOG4J;

        foreach ($this->versionMap as $version => $versionData) {
            $destination = $this->directoryList->getImagesRoot() . '/elasticsearch/' . $version;
            $dataDir = $this->directoryList->getImagesRoot() . '/elasticsearch/es/';
            $dockerfile = $destination . '/Dockerfile';

            $this->filesystem->deleteDirectory($destination);
            $this->filesystem->makeDirectory($destination);
            $this->filesystem->copyDirectory($dataDir, $destination);
            $this->filesystem->chmod($destination . '/docker-entrypoint.sh', 0755);

            $this->filesystem->put(
                $dockerfile,
                strtr(
                    $this->filesystem->get($dockerfile),
                    [
                        '{%version%}' => $versionData['real-version'],
                        '{%single_node%}' => $versionData['single-node'] ? self::SINGLE_NODE : '',
                        '{%log4j_fix%}' => in_array($version, ['7.10', '7.11'])
                            ? sprintf($log4jFix, $fixRepo)
                            : '',
                    ]
                )
            );
        }

        $output->writeln('<info>Done</info>');

        return Cli::SUCCESS;
    }
}
