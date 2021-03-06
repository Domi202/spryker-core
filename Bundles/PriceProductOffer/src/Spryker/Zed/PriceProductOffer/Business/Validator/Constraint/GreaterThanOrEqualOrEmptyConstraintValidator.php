<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\PriceProductOffer\Business\Validator\Constraint;

use Symfony\Component\Validator\Constraints\AbstractComparisonValidator;

class GreaterThanOrEqualOrEmptyConstraintValidator extends AbstractComparisonValidator
{
    /**
     * @param mixed $value1
     * @param mixed $value2
     *
     * @return bool
     */
    protected function compareValues($value1, $value2)
    {
        return $value1 === null || $value1 >= $value2;
    }
}
