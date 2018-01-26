<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductCustomerPermission;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\ProductCustomerPermission\Dependency\Client\ProductCustomerPermissionToCustomerClientClientBridge;

class ProductCustomerPermissionDependencyProvider extends AbstractDependencyProvider
{
    const CLIENT_CUSTOMER = 'CLIENT_CUSTOMER';

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    public function provideServiceLayerDependencies(Container $container)
    {
        $container = $this->addCustomerClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addCustomerClient(Container $container)
    {
        $container[static::CLIENT_CUSTOMER] = function (Container $container) {
            return new ProductCustomerPermissionToCustomerClientClientBridge($container->getLocator()->customer()->client());
        };
        return $container;
    }
}
