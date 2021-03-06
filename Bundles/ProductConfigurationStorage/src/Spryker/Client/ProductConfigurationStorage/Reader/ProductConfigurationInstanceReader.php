<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductConfigurationStorage\Reader;

use Generated\Shared\Transfer\ProductConfigurationInstanceTransfer;
use Spryker\Client\ProductConfigurationStorage\Builder\ProductConfigurationSessionKeyBuilderInterface;
use Spryker\Client\ProductConfigurationStorage\Dependency\Client\ProductConfigurationStorageToSessionClientInterface;
use Spryker\Client\ProductConfigurationStorage\Mapper\ProductConfigurationInstanceMapperInterface;
use Spryker\Client\ProductConfigurationStorage\Storage\ProductConfigurationStorageReaderInterface;

class ProductConfigurationInstanceReader implements ProductConfigurationInstanceReaderInterface
{
    /**
     * @var \Spryker\Client\ProductConfigurationStorage\Storage\ProductConfigurationStorageReaderInterface
     */
    protected $configurationStorageReader;

    /**
     * @var \Spryker\Client\ProductConfigurationStorage\Dependency\Client\ProductConfigurationStorageToSessionClientInterface
     */
    protected $sessionClient;

    /**
     * @var \Spryker\Client\ProductConfigurationStorage\Mapper\ProductConfigurationInstanceMapperInterface
     */
    protected $productConfigurationStorageMapper;

    /**
     * @var \Spryker\Client\ProductConfigurationStorage\Builder\ProductConfigurationSessionKeyBuilderInterface
     */
    protected $productConfigurationSessionKeyBuilder;

    /**
     * @param \Spryker\Client\ProductConfigurationStorage\Storage\ProductConfigurationStorageReaderInterface $configurationStorageReader
     * @param \Spryker\Client\ProductConfigurationStorage\Dependency\Client\ProductConfigurationStorageToSessionClientInterface $sessionClient
     * @param \Spryker\Client\ProductConfigurationStorage\Mapper\ProductConfigurationInstanceMapperInterface $productConfigurationStorageMapper
     * @param \Spryker\Client\ProductConfigurationStorage\Builder\ProductConfigurationSessionKeyBuilderInterface $productConfigurationSessionKeyBuilder
     */
    public function __construct(
        ProductConfigurationStorageReaderInterface $configurationStorageReader,
        ProductConfigurationStorageToSessionClientInterface $sessionClient,
        ProductConfigurationInstanceMapperInterface $productConfigurationStorageMapper,
        ProductConfigurationSessionKeyBuilderInterface $productConfigurationSessionKeyBuilder
    ) {
        $this->configurationStorageReader = $configurationStorageReader;
        $this->sessionClient = $sessionClient;
        $this->productConfigurationStorageMapper = $productConfigurationStorageMapper;
        $this->productConfigurationSessionKeyBuilder = $productConfigurationSessionKeyBuilder;
    }

    /**
     * @param string $sku
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationInstanceTransfer|null
     */
    public function findProductConfigurationInstanceBySku(string $sku): ?ProductConfigurationInstanceTransfer
    {
        $productConfigurationSessionKey = $this->productConfigurationSessionKeyBuilder->getProductConfigurationSessionKey($sku);
        $productConfigurationInstanceTransfer = $this->sessionClient->get($productConfigurationSessionKey);

        if ($productConfigurationInstanceTransfer) {
            return (new ProductConfigurationInstanceTransfer())
                ->fromArray($productConfigurationInstanceTransfer->toArray());
        }

        return $this->findProductConfigurationInstanceInStorage($sku);
    }

    /**
     * @param string $sku
     *
     * @return \Generated\Shared\Transfer\ProductConfigurationInstanceTransfer|null
     */
    protected function findProductConfigurationInstanceInStorage(string $sku): ?ProductConfigurationInstanceTransfer
    {
        $productConfigurationStorageTransfer = $this->configurationStorageReader
            ->findProductConfigurationStorageBySku($sku);

        if (!$productConfigurationStorageTransfer) {
            return null;
        }

        return $this->productConfigurationStorageMapper
            ->mapProductConfigurationStorageTransferToProductConfigurationInstanceTransfer(
                $productConfigurationStorageTransfer,
                new ProductConfigurationInstanceTransfer()
            );
    }
}
