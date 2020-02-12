<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CloudDocker\Command\BuildCompose;

use Magento\CloudDocker\App\GenericException;
use Magento\CloudDocker\Command\BuildCompose;
use Magento\CloudDocker\Compose\ProductionBuilder;

/**
 * Validator for a value of the split-db option
 */
class SplitDbOptionValidator
{
    /**
     * @param array $splitDbTypes
     * @throws GenericException
     */
    public function validate(array $splitDbTypes)
    {
        $invalidSplitDbTypes = array_diff($splitDbTypes, ProductionBuilder::SPLIT_DB_TYPES);
        if (!empty($invalidSplitDbTypes)) {
            throw new GenericException(sprintf(
                'The value [%s] is invalid of the option `%s`. Available: [%s]',
                implode(' ', $invalidSplitDbTypes),
                BuildCompose::OPTION_SPLIT_DB,
                implode(' ', ProductionBuilder::SPLIT_DB_TYPES)
            ));
        }
    }
}
