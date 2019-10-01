<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\MagentoCloud\Test\Sniffs\Directives;

use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Files\File;

/**
 * Sniffer to check if the strict_types declaration is included and add it if not.
 *
 * Class StrictTypesSniff
 */
class StrictTypesSniff implements Sniff
{
    /**
     * Flag to keep track of whether the file has been fixed.
     *
     * @var bool
     */
    private $fixed = false;

    /**
     * @return array
     */
    public function register()
    {
        return [
            T_OPEN_TAG
        ];
    }

    /**
     * @param File $phpcsFile
     * @param int $stackPtr
     * @return int|void
     */
    public function process(File $phpcsFile, $stackPtr)
    {
        // Get all tokens.
        $tokens = $phpcsFile->getTokens();

        // Tokens to look for.
        $findTokens = [
            T_DECLARE,
            T_NAMESPACE,
            T_CLASS
        ];

        // Find the first occurrence of the tokens to look for.
        $position = $phpcsFile->findNext($findTokens, $stackPtr);

        // If the first token found is not T_DECLARE, then the file does not include a strict_types declaration.
        if($tokens[$position]['code'] !== T_DECLARE) {
            // Fix and set the boolean flag to true.
            $this->fix($phpcsFile, $position);
        }

        // If the file includes a declare directive, and the file has not already been fixed, scan specifically
        // for strict_types and fix as needed.
        if(!$this->fixed) {
            if(!$this->scan($phpcsFile, $tokens, $position)) {
                $this->fix($phpcsFile, $position);
            }
        }
    }

    /**
     * Fixer to add the strict_types declaration.
     *
     * @param File $phpcsFile
     * @param int $position
     */
    private function fix(File $phpcsFile, int $position) : void
    {
        // Get the fixer.
        $fixer = $phpcsFile->fixer;
        // Record the error.
        $phpcsFile->addFixableError("Missing strict_types declaration", $position, self::class);
        // Prepend content at the given position.
        $fixer->addContentBefore($position, "declare(strict_types=1);\n\n");
        // Set flag.
        $this->fixed = true;
    }

    /**
     * Recursive method to scan declare statements for strict_types.
     *
     * @param File $phpcsFile
     * @param array $tokens
     * @param int $position
     * @return bool
     */
    private function scan(File $phpcsFile, array $tokens, int $position) : bool
    {
        // Exit statement, if the beginning of the file has been reached.
        if($tokens[$position]['code'] === T_OPEN_TAG || $position === 0) {
            return false;
        }

        if(!$phpcsFile->findNext([T_STRING], $position)) {
            // If there isn't a T_STRING token for the declare directive, continue scan.
            return $this->scan($phpcsFile, $tokens, $phpcsFile->findPrevious([T_DECLARE], $position - 1));
        } else {
            // Checking specifically for strict_types.
            $temp = $phpcsFile->findNext([T_STRING], $position);
            if($tokens[$temp]['content'] === 'strict_types') {
                // Return true as strict_types directive has been found.
                return true;
            } else {
                // Continue scan if strict_types hasn't been found.
                return $this->scan($phpcsFile, $tokens, $phpcsFile->findPrevious([T_DECLARE], $position - 1));
            }
        }
    }
}
