<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Config\Dist;

/**
 * Formats PHP array into exportable string.
 */
class Formatter
{
    /**
     * 4 space indentation for array formatting.
     */
    private const INDENT = '    ';

    /**
     * If variable to export is an array, format with the php >= 5.4 short array syntax. Otherwise use
     * default var_export functionality.
     *
     * @param mixed $var
     * @param int $depth
     * @return string
     */
    public function varExport($var, int $depth = 1): string
    {
        if (!is_array($var)) {
            return var_export($var, true);
        }

        $indexed = array_keys($var) === range(0, count($var) - 1);
        $expanded = [];
        foreach ($var as $key => $value) {
            $expanded[] = str_repeat(self::INDENT, $depth)
                . ($indexed ? '' : $this->varExport($key) . ' => ')
                . $this->varExport($value, $depth + 1);
        }

        return sprintf("[\n%s\n%s]", implode(",\n", $expanded), str_repeat(self::INDENT, $depth - 1));
    }
}
