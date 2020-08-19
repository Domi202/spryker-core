<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\CmsNavigationConnector;

use Codeception\Actor;
use Spryker\Zed\Navigation\Business\NavigationFacadeInterface;

/**
 * Inherited Methods
 *
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 * @method \Codeception\Lib\Friend haveFriend($name, $actorClass = null)
 *
 * @SuppressWarnings(PHPMD)
 */
class CmsNavigationConnectorBusinessTester extends Actor
{
    use _generated\CmsNavigationConnectorBusinessTesterActions;

    /**
     * @return \Spryker\Zed\Navigation\Business\NavigationFacadeInterface
     */
    public function getNavigationFacade(): NavigationFacadeInterface
    {
        return $this->getLocator()->navigation()->facade();
    }
}
