<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\Dev;

use Magento\CloudDocker\Filesystem\DirectoryList;
use Magento\CloudDocker\Filesystem\Filesystem;

class ComposerUpdater
{
    /**
     * @var DirectoryList
     */
    private $directoryList;

    private $filesystem;

    public function __construct(DirectoryList $directoryList, Filesystem $filesystem)
    {
        $this->directoryList = $directoryList;
        $this->filesystem = $filesystem;
    }

    public function clean()
    {
        $config = json_encode($this->filesystem->get($this->directoryList->getMagentoRootComposer()), true);

        foreach (array_keys($config['repositories']) as $repo) {
            if (strpos($repo, 'dev-') === 0) {
                unset($config['repositories'][$repo]);
            }
        }

        foreach (array_keys($config['repositories']) as $repo) {
            if (strpos($repo, 'dev-') === 0) {
                unset($config['require'][$repo]);
            }
        }

        $this->filesystem->put($this->directoryList->getMagentoRootComposer(), json_encode($config));
    }
}
