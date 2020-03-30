<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Source;

use Illuminate\Config\Repository;

/**
 * Custom source.
 */
class CustomSource implements SourceInterface
{
    /**
     * @var Repository
     */
    private $data;

    /**
     * @param array $data
     */
    public function __construct(array $data)
    {
        $repo = new Repository();
        $repo->set($data);

        $this->data = $repo;
    }

    /**
     * @return Repository
     */
    public function read(): Repository
    {
        return $this->data;
    }
}
