<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductDiscontinued\Business\ProductDiscontinued;

use Generated\Shared\Transfer\ProductDiscontinuedCollectionTransfer;
use Generated\Shared\Transfer\ProductDiscontinuedTransfer;

interface ProductDiscontinuedPluginExecutorInterface
{
    /**
     * @param \Generated\Shared\Transfer\ProductDiscontinuedTransfer $productDiscontinuedTransfer
     *
     * @return void
     */
    public function executePostProductDiscontinuePlugins(ProductDiscontinuedTransfer $productDiscontinuedTransfer): void;

    /**
     * @deprecated Use {@link \Spryker\Zed\ProductDiscontinued\Business\ProductDiscontinued\ProductDiscontinuedPluginExecutorInterface::executeBulkPostDeleteProductDiscontinuedPlugins()} instead.
     *
     * @param \Generated\Shared\Transfer\ProductDiscontinuedTransfer $productDiscontinuedTransfer
     *
     * @return void
     */
    public function executePostDeleteProductDiscontinuedPlugins(ProductDiscontinuedTransfer $productDiscontinuedTransfer): void;

    /**
     * @param \Generated\Shared\Transfer\ProductDiscontinuedCollectionTransfer $productDiscontinuedCollectionTransfer
     *
     * @return void
     */
    public function executeBulkPostDeleteProductDiscontinuedPlugins(ProductDiscontinuedCollectionTransfer $productDiscontinuedCollectionTransfer): void;
}
