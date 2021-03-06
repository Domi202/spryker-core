<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\CartCode\Operation;

use Generated\Shared\Transfer\CartCodeOperationResultTransfer;
use Generated\Shared\Transfer\QuoteTransfer;

/**
 * @deprecated Will be removed in the next major version.
 */
interface CodeRemoverInterface
{
    /**
     * @deprecated Will be removed in the next major version.
     *
     * @param \Generated\Shared\Transfer\QuoteTransfer $quoteTransfer
     * @param string $code
     *
     * @return \Generated\Shared\Transfer\CartCodeOperationResultTransfer
     */
    public function remove(QuoteTransfer $quoteTransfer, string $code): CartCodeOperationResultTransfer;
}
