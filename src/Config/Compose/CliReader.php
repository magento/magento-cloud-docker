<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Compose;

use Illuminate\Config\Repository;
use Symfony\Component\Console\Input\InputInterface;

class CliReader implements ReaderInterface
{
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

    public function read(): Repository
    {
        return new Repository();
    }
}
