<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\MerchantProfile\Communication\Plugin\Merchant;

use Generated\Shared\Transfer\MerchantProfileCriteriaFilterTransfer;
use Generated\Shared\Transfer\MerchantTransfer;
use Spryker\Zed\Kernel\Communication\AbstractPlugin;
use Spryker\Zed\MerchantExtension\Dependency\Plugin\MerchantExpanderPluginInterface;

/**
 * @method \Spryker\Zed\MerchantProfile\Business\MerchantProfileFacadeInterface getFacade()
 * @method \Spryker\Zed\MerchantProfile\Communication\MerchantProfileCommunicationFactory getFactory()
 * @method \Spryker\Zed\MerchantProfile\MerchantProfileConfig getConfig()
 */
class MerchantProfileExpanderPlugin extends AbstractPlugin implements MerchantExpanderPluginInterface
{
    /**
     * {@inheritDoc}
     * - Expands merchant by merchant profile data.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\MerchantTransfer $merchantTransfer
     *
     * @return \Generated\Shared\Transfer\MerchantTransfer
     */
    public function expand(MerchantTransfer $merchantTransfer): MerchantTransfer
    {
        $merchantProfileCriteriaFilterTransfer = $this->createMerchantProfileCriteriaFilterTransfer($merchantTransfer);
        $merchantProfileTransfer = $this->getFacade()->findOne($merchantProfileCriteriaFilterTransfer);

        if ($merchantProfileTransfer !== null) {
            $merchantTransfer->setMerchantProfile($merchantProfileTransfer);
        }

        return $merchantTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\MerchantTransfer $merchantTransfer
     *
     * @return \Generated\Shared\Transfer\MerchantProfileCriteriaFilterTransfer
     */
    protected function createMerchantProfileCriteriaFilterTransfer(MerchantTransfer $merchantTransfer): MerchantProfileCriteriaFilterTransfer
    {
        $merchantProfileCriteriaFilterTransfer = new MerchantProfileCriteriaFilterTransfer();
        $merchantProfileCriteriaFilterTransfer->setFkMerchant($merchantTransfer->getIdMerchant());

        return $merchantProfileCriteriaFilterTransfer;
    }
}
