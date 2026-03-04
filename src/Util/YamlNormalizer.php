<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Util;

use Symfony\Component\Yaml\Tag\TaggedValue;
use Symfony\Component\Yaml\Yaml;

/**
 * Centralized YAML normalization utility.
 *
 * Converts Symfony YAML TaggedValue objects into PHP-native data types.
 */
class YamlNormalizer
{
    /**
     * Recursively normalizes Symfony YAML TaggedValue objects into PHP-native values.
     *
     * Handles the following YAML tags:
     *  - !env: resolves environment variables.
     *  - !include: parses and normalizes included YAML files.
     *  - !php/const: resolves PHP constants (e.g. !php/const:\PDO::ATTR_ERRMODE).
     *  - Other or unknown tags: recursively normalize their values.
     *
     * Ensures all YAML data is converted to scalars or arrays suitable for safe merging.
     *
     * @param mixed $data The parsed YAML data (array, scalar, or TaggedValue).
     * @return mixed The normalized data structure.
     *
     * @SuppressWarnings("PHPMD.NPathComplexity")
     * @SuppressWarnings("PHPMD.CyclomaticComplexity") Method is intentionally complex due to tag resolution logic.
     */
    public function normalize(mixed $data): mixed
    {
        if ($data instanceof TaggedValue) {
            $tag   = $data->getTag();   // e.g. "php/const:\PDO::MYSQL_ATTR_LOCAL_INFILE"
            $value = $data->getValue();

            // Handle php/const tags (Symfony strips leading '!')
            if (str_starts_with($tag, 'php/const:')) {
                // Extract the constant name
                $constName = substr($tag, strlen('php/const:'));
                $constName = ltrim($constName, '\\');

                // Resolve the constant name to its value if defined
                $constKey = defined($constName) ? constant($constName) : $constName;

                // Handle YAML quirk where ": 1" is parsed literally
                $raw = is_string($value) ? $value : (string)$value;
                $cleanVal = str_replace([':', ' '], '', $raw);
                $constVal = is_numeric($cleanVal) ? (int)$cleanVal : $cleanVal;

                return [$constKey => $constVal];
            }

            // Handle !env
            if ($tag === 'env') {
                $envValue = getenv((string)$value);
                return $envValue !== false ? $envValue : null;
            }

            // Handle !include
            if ($tag === 'include') {
                if (file_exists((string)$value)) {
                    $included = Yaml::parseFile((string)$value);
                    return $this->normalize($included);
                }
                return null;
            }

            // Default — recursively normalize nested tagged structures
            $normalized = $this->normalize($value);
            return is_array($normalized) ? $normalized : [$normalized];
        }

        // Recursively normalize arrays
        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->normalize($value);
            }
        }

        return $data;
    }
}
