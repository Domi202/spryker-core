<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Client\ProductQuantityStorage\Expander;

use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\MessageTransfer;
use Generated\Shared\Transfer\ProductQuantityStorageTransfer;
use Spryker\Client\ProductQuantityStorage\Resolver\ProductQuantityResolverInterface;
use Spryker\Client\ProductQuantityStorage\Storage\ProductQuantityStorageReaderInterface;

class QuantityCartChangeItemExpander implements QuantityCartChangeItemExpanderInterface
{
    protected const NOTIFICATION_MESSAGE_QUANTITY_MIN_NOT_FULFILLED = 'product-quantity.notification.quantity.min.failed';
    protected const NOTIFICATION_MESSAGE_QUANTITY_MAX_NOT_FULFILLED = 'product-quantity.notification.quantity.max.failed';
    protected const NOTIFICATION_MESSAGE_QUANTITY_INTERVAL_NOT_FULFILLED = 'product-quantity.notification.quantity.interval.failed';
    protected const NOTIFICATION_MESSAGE_PARAM_MIN = '%min%';
    protected const NOTIFICATION_MESSAGE_PARAM_MAX = '%max%';
    protected const NOTIFICATION_MESSAGE_PARAM_STEP = '%step%';
    protected const FIELD_QUANTITY = 'quantity';

    /**
     * @var \Spryker\Client\ProductQuantityStorage\Storage\ProductQuantityStorageReaderInterface
     */
    protected $productQuantityStorageReader;

    /**
     * @var \Spryker\Client\ProductQuantityStorage\Resolver\ProductQuantityResolverInterface
     */
    protected $productQuantityResolver;

    /**
     * @param \Spryker\Client\ProductQuantityStorage\Storage\ProductQuantityStorageReaderInterface $productQuantityStorageReader
     * @param \Spryker\Client\ProductQuantityStorage\Resolver\ProductQuantityResolverInterface $productQuantityResolver
     */
    public function __construct(
        ProductQuantityStorageReaderInterface $productQuantityStorageReader,
        ProductQuantityResolverInterface $productQuantityResolver
    ) {
        $this->productQuantityStorageReader = $productQuantityStorageReader;
        $this->productQuantityResolver = $productQuantityResolver;
    }

    /**
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    public function expandCartChangeItem(ItemTransfer $itemTransfer): ItemTransfer
    {
        $productConcreteTransfer = $itemTransfer->getProductConcrete();

        if (!$productConcreteTransfer || $productConcreteTransfer->getIdProductConcrete() === null) {
            return $itemTransfer;
        }

        $productQuantityStorageTransfer = $this->productQuantityStorageReader
            ->findProductQuantityStorage($productConcreteTransfer->getIdProductConcrete());

        if (!$productQuantityStorageTransfer) {
            return $itemTransfer;
        }

        return $this->expandCartChangeItemQuantity($productQuantityStorageTransfer, $itemTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\ProductQuantityStorageTransfer $productQuantityStorageTransfer
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    protected function expandCartChangeItemQuantity(
        ProductQuantityStorageTransfer $productQuantityStorageTransfer,
        ItemTransfer $itemTransfer
    ): ItemTransfer {
        $min = $productQuantityStorageTransfer->getQuantityMin();
        $max = $productQuantityStorageTransfer->getQuantityMax();
        $interval = $productQuantityStorageTransfer->getQuantityInterval();
        $quantity = $itemTransfer->getQuantity();

        if (($quantity === 0 || $quantity > $min)
            && ($quantity === 0 || ($quantity - $min) % $interval === 0)
            && ($max === null || $quantity < $max)
        ) {
            return $itemTransfer;
        }

        $nearestQuantity = $this->productQuantityResolver->getNearestQuantity(
            $itemTransfer->getProductConcrete()->getIdProductConcrete(),
            $quantity
        );
        $itemTransfer->setQuantity($nearestQuantity);

        $itemTransfer->addNotificationMessage(
            $this->getNotificationMessageBasedOnQuantityRestriction($quantity, $nearestQuantity, $productQuantityStorageTransfer)
        );

        return $itemTransfer;
    }

    /**
     * @param int $quantity
     * @param int $nearestQuantity
     * @param \Generated\Shared\Transfer\ProductQuantityStorageTransfer $productQuantityStorageTransfer
     *
     * @return \Generated\Shared\Transfer\MessageTransfer
     */
    protected function getNotificationMessageBasedOnQuantityRestriction(
        int $quantity,
        int $nearestQuantity,
        ProductQuantityStorageTransfer $productQuantityStorageTransfer
    ): MessageTransfer {
        $min = $productQuantityStorageTransfer->getQuantityMin();
        $max = $productQuantityStorageTransfer->getQuantityMax();
        $interval = $productQuantityStorageTransfer->getQuantityInterval();

        if ($quantity !== 0 && $quantity < $min) {
            return (new MessageTransfer())
                ->setValue(static::NOTIFICATION_MESSAGE_QUANTITY_MIN_NOT_FULFILLED)
                ->setParameters([static::NOTIFICATION_MESSAGE_PARAM_MIN => $nearestQuantity]);
        }

        if ($quantity !== 0 && ($quantity - $min) % $interval !== 0) {
            return (new MessageTransfer())
                ->setValue(static::NOTIFICATION_MESSAGE_QUANTITY_INTERVAL_NOT_FULFILLED)
                ->setParameters([static::NOTIFICATION_MESSAGE_PARAM_STEP => $nearestQuantity]);
        }

        if ($max !== null && $quantity > $max) {
            return (new MessageTransfer())
                ->setValue(static::NOTIFICATION_MESSAGE_QUANTITY_MAX_NOT_FULFILLED)
                ->setParameters([static::NOTIFICATION_MESSAGE_PARAM_MAX => $nearestQuantity]);
        }

        return new MessageTransfer();
    }
}
