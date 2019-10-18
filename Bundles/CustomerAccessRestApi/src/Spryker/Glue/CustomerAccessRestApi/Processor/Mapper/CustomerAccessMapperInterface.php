<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Glue\CustomerAccessRestApi\Processor\Mapper;

use Generated\Shared\Transfer\RestCustomerAccessAttributesTransfer;

interface CustomerAccessMapperInterface
{
    /**
     * @param array $customerAccessContentTypeResourceTypes
     * @param \Generated\Shared\Transfer\RestCustomerAccessAttributesTransfer $restCustomerAccessAttributesTransfer
     *
     * @return \Generated\Shared\Transfer\RestCustomerAccessAttributesTransfer
     */
    public function mapCustomerAccessContentTypeResourceTypeToRestCustomerAccessAttributesTransfer(
        array $customerAccessContentTypeResourceTypes,
        RestCustomerAccessAttributesTransfer $restCustomerAccessAttributesTransfer
    ): RestCustomerAccessAttributesTransfer;
}
