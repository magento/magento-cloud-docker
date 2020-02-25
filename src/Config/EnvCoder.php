<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config;

/**
 * Encoder/Decoder for MAGENTO_CLOUD_* variables
 */
class EnvCoder
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
    ];

    /**
     * @param array $variables
     * @return array
     */
    public function encode(array $variables): array
    {
        array_walk($variables, static function(&$value, $key){
            if (in_array($key, self::$encodedEnv, true)) {
                $value = base64_encode(json_encode($value));
            }
        });

        return $variables;
    }

    /**
     * @param array $variables
     * @return array
     */
    public function decode(array $variables): array
    {
        array_walk($variables, static function(&$value, $key){
            if (in_array($key, self::$encodedEnv, true)) {
                $value = json_decode(base64_decode($value), true);
            }
        });

        return $variables;
    }
}
