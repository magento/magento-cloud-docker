<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Environment;

/**
 * Converter for Docker environment variables.
 */
class Converter
{
    /**
     * Converts array to .env notation.
     *
     * @param array $variables
     * @return array
     */
    public function convert(array $variables): array
    {
        $data = [];

        foreach ($variables as $variable => $value) {
            $formattedValue = is_bool($value) ? var_export($value, true) : $value;
            $data [] = $variable . '=' . $formattedValue;
        }

        return $data;
    }
}
