<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Environment;

use Magento\CloudDocker\App\ConfigurationMismatchException;

/**
 * Encoder/Decoder for MAGENTO_CLOUD_* variables
 */
class Encoder
{
    /**
     * The variables list to encode/decode base64
     *
     * @var array
     */
    private static $encodedEnv = [
        'MAGENTO_CLOUD_RELATIONSHIPS',
        'MAGENTO_CLOUD_ROUTES',
        'MAGENTO_CLOUD_VARIABLES',
        'MAGENTO_CLOUD_APPLICATION',
    ];

    /**
     * Encodes needed variables from the list
     *
     * @param array $variables
     *
     * @return array
     * @throws ConfigurationMismatchException
     *
     * @see $encodedEnv
     */
    public function encode(array $variables): array
    {
        foreach ($variables as $key => &$value) {
            if (in_array($key, self::$encodedEnv, true)) {
                $value = base64_encode(json_encode($value));
            } elseif (!is_scalar($value)) {
                throw new ConfigurationMismatchException(sprintf(
                    'Value of %s must have a scalar type',
                    $key
                ));
            }
        }

        return $variables;
    }

    /**
     * Decodes needed variables from the list
     *
     * @param array $variables
     *
     * @return array
     *
     * @see $encodedEnv
     */
    public function decode(array $variables): array
    {
        array_walk($variables, static function (&$value, $key) {
            if (in_array($key, self::$encodedEnv, true)) {
                $value = json_decode(base64_decode($value), true);
            }
        });

        return $variables;
    }
}
