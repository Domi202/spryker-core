<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\SecurityBlockerRedis;

use Spryker\Client\Kernel\AbstractDependencyProvider;
use Spryker\Client\Kernel\Container;
use Spryker\Client\SecurityBlockerRedis\Dependency\Client\SecurityBlockerRedisToRedisClientBridge;

class SecurityBlockerRedisDependencyProvider extends AbstractDependencyProvider
{
    public const CLIENT_REDIS = 'CLIENT_REDIS';

    public function provideServiceLayerDependencies(Container $container): Container
    {
        $container = parent::provideServiceLayerDependencies($container);
        $container = $this->addRedisClient($container);

        return $container;
    }

    /**
     * @param \Spryker\Client\Kernel\Container $container
     *
     * @return \Spryker\Client\Kernel\Container
     */
    protected function addRedisClient(Container $container): Container
    {
        $container->set(static::CLIENT_REDIS, function(Container $container) {
            return new SecurityBlockerRedisToRedisClientBridge(
                $container->getLocator()->redis()->client()
            );
        });

        return $container;
    }
}
