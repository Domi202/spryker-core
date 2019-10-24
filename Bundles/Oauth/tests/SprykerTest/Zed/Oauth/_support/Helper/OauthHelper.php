<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Oauth\Helper;

use Codeception\Module;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\OauthRequestTransfer;
use Generated\Shared\Transfer\OauthResponseTransfer;
use Spryker\Zed\Oauth\OauthConfig;
use SprykerTest\Shared\Testify\Helper\LocatorHelperTrait;

class OauthHelper extends Module
{
    use LocatorHelperTrait;

    /**
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     *
     * @return \Generated\Shared\Transfer\OauthResponseTransfer
     */
    public function haveAuth(CustomerTransfer $customerTransfer): OauthResponseTransfer
    {
        $oauthRequestTransfer = (new OauthRequestTransfer())
            ->setGrantType(OauthConfig::GRANT_TYPE_PASSWORD)
            ->setUsername($customerTransfer->getEmail())
            ->setPassword($customerTransfer->getNewPassword());

        return $this->getLocator()->oauth()->facade()
            ->processAccessTokenRequest($oauthRequestTransfer);
    }
}
