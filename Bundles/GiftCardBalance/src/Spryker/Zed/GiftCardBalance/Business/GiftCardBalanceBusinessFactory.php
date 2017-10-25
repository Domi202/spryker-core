<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\GiftCardBalance\Business;

use Spryker\Zed\Kernel\Business\AbstractBusinessFactory;

/**
 * @method \Spryker\Zed\GiftCardBalance\GiftCardBalanceConfig getConfig()
 * @method \Spryker\Zed\GiftCardBalance\Persistence\GiftCardBalanceQueryContainer getQueryContainer()
 */
class GiftCardBalanceBusinessFactory extends AbstractBusinessFactory
{
    /**
     * @return \Spryker\Zed\GiftCardBalance\Business\GiftCardBalanceCheckerInterface
     */
    public function createGiftCardBalanceChecker()
    {
        return new GiftCardBalanceChecker(
            $this->getQueryContainer()
        );
    }

    /**
     * @return \Spryker\Zed\GiftCardBalance\Business\GiftCardBalanceSaverInterface
     */
    public function createGiftCardBalanceSaver()
    {
        return new GiftCardBalanceSaver();
    }
}
