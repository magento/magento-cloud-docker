<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

use Illuminate\Config\Repository;
use Magento\CloudDocker\Config\Compose\ReaderInterface;

class Compose implements ReaderInterface
{
    /**
     * @var ReaderInterface
     */
    private $readers;

    /**
     * @param ReaderInterface[] $readers
     */
    public function __construct(array $readers)
    {
        $this->readers = $readers;
    }

    /***
     * @return array
     */
    public function read(): Repository
    {
        $data = [];

        foreach ($this->readers as $reader) {
            $data = array_replace($data, $reader->read()->all());
        }

        return new Repository($data);
    }
}
