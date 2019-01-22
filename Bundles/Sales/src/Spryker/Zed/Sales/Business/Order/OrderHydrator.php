<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Sales\Business\Order;

use Generated\Shared\Transfer\AddressTransfer;
use Generated\Shared\Transfer\CountryTransfer;
use Generated\Shared\Transfer\ExpenseTransfer;
use Generated\Shared\Transfer\ItemTransfer;
use Generated\Shared\Transfer\OrderTransfer;
use Generated\Shared\Transfer\ShipmentCarrierTransfer;
use Generated\Shared\Transfer\ShipmentMethodTransfer;
use Generated\Shared\Transfer\ShipmentTransfer;
use Orm\Zed\Sales\Persistence\SpySalesExpense;
use Orm\Zed\Sales\Persistence\SpySalesOrder;
use Orm\Zed\Sales\Persistence\SpySalesOrderAddress;
use Orm\Zed\Sales\Persistence\SpySalesOrderItem;
use Spryker\Zed\Sales\Business\Exception\InvalidSalesOrderException;
use Spryker\Zed\Sales\Business\Model\Order\OrderHydrator as OrderHydratorWithoutMultiShipping;

class OrderHydrator extends OrderHydratorWithoutMultiShipping
{
    /**
     * @param int $idSalesOrder
     *
     * @throws \Spryker\Zed\Sales\Business\Exception\InvalidSalesOrderException
     *
     * @return \Generated\Shared\Transfer\OrderTransfer
     */
    public function hydrateOrderTransferFromPersistenceByIdSalesOrder($idSalesOrder)
    {
        $orderEntity = $this->queryContainer
            ->querySalesOrderDetailsWithoutShippingAddress($idSalesOrder)
            ->findOne();

        if ($orderEntity === null) {
            throw new InvalidSalesOrderException(
                sprintf(
                    'Order could not be found for ID %s',
                    $idSalesOrder
                )
            );
        }

        return $this->hydrateOrderTransferFromPersistenceBySalesOrder($orderEntity);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     *
     * @return \Generated\Shared\Transfer\ItemTransfer
     */
    public function hydrateOrderItemTransfer(SpySalesOrderItem $orderItemEntity): ItemTransfer
    {
        $itemTransfer = parent::hydrateOrderItemTransfer($orderItemEntity);

        if ($orderItemEntity->getSpySalesShipment() !== null) {
            $this->hydrateItemShipping($orderItemEntity, $itemTransfer);
        }

        return $itemTransfer;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return void
     */
    protected function hydrateBillingAddressToOrderTransfer(SpySalesOrder $orderEntity, OrderTransfer $orderTransfer): void
    {
        $orderTransfer->setBillingAddress(
            $this->createAddressTransfer($orderEntity->getBillingAddress())
        );
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return void
     */
    protected function hydrateShippingAddressToOrderTransfer(SpySalesOrder $orderEntity, OrderTransfer $orderTransfer): void
    {
        $shippingAddress = $orderEntity->getShippingAddress();

        if ($shippingAddress === null) {
            return;
        }

        $orderTransfer->setShippingAddress($this->createAddressTransfer($shippingAddress));
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderAddress $salesOrderAddressEntity
     *
     * @return \Generated\Shared\Transfer\AddressTransfer
     */
    protected function createAddressTransfer(SpySalesOrderAddress $salesOrderAddressEntity): AddressTransfer
    {
        $countryEntity = $salesOrderAddressEntity->getCountry();

        $addressTransfer = new AddressTransfer();
        $addressTransfer->fromArray($salesOrderAddressEntity->toArray(), true);
        $addressTransfer->setIso2Code($countryEntity->getIso2Code());

        $countryTransfer = (new CountryTransfer())->fromArray($countryEntity->toArray(), true);
        $addressTransfer->setCountry($countryTransfer);

        return $addressTransfer;
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrder $orderEntity
     * @param \Generated\Shared\Transfer\OrderTransfer $orderTransfer
     *
     * @return void
     */
    protected function hydrateExpensesToOrderTransfer(SpySalesOrder $orderEntity, OrderTransfer $orderTransfer): void
    {
        foreach ($orderEntity->getExpenses() as $expenseEntity) {
            $orderTransfer->addExpense($this->createExpense($expenseEntity));
        }
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesOrderItem $orderItemEntity
     * @param \Generated\Shared\Transfer\ItemTransfer $itemTransfer
     *
     * @return void
     */
    protected function hydrateItemShipping(SpySalesOrderItem $orderItemEntity, ItemTransfer $itemTransfer): void
    {
        $spySalesShipment = $orderItemEntity->getSpySalesShipment();

        $shipmentTransfer = new ShipmentTransfer();
        $shipmentTransfer->setShippingAddress(
            $this->createAddressTransfer($spySalesShipment->getSpySalesOrderAddress())
        );
        $shipmentTransfer->setExpense($this->createExpense($spySalesShipment->getExpense()));
        $shipmentTransfer->setCarrier($this->createShipmentCarrier($spySalesShipment->getCarrierName()));
        $shipmentTransfer->setMethod($this->createShipmentMethod($spySalesShipment->getName()));
        $shipmentTransfer->setRequestedDeliveryDate($spySalesShipment->getRequestedDeliveryDate());

        $itemTransfer->setShipment($shipmentTransfer);
    }

    /**
     * @param string $shipmentMethod
     *
     * @return \Generated\Shared\Transfer\ShipmentMethodTransfer
     */
    protected function createShipmentMethod(string $shipmentMethod): ShipmentMethodTransfer
    {
        return (new ShipmentMethodTransfer())
            ->setName($shipmentMethod);
    }

    /**
     * @param string $carrierName
     *
     * @return \Generated\Shared\Transfer\ShipmentCarrierTransfer
     */
    protected function createShipmentCarrier(string $carrierName): ShipmentCarrierTransfer
    {
        return (new ShipmentCarrierTransfer())
            ->setName($carrierName);
    }

    /**
     * @param \Orm\Zed\Sales\Persistence\SpySalesExpense $expenseEntity
     *
     * @return \Generated\Shared\Transfer\ExpenseTransfer
     */
    protected function createExpense(SpySalesExpense $expenseEntity): ExpenseTransfer
    {
        $expenseTransfer = new ExpenseTransfer();
        $expenseTransfer->fromArray($expenseEntity->toArray(), true);

        $expenseTransfer->setQuantity(1);
        $expenseTransfer->setSumGrossPrice($expenseEntity->getGrossPrice());
        $expenseTransfer->setSumNetPrice($expenseEntity->getNetPrice());
        $expenseTransfer->setSumPrice($expenseEntity->getPrice());
        $expenseTransfer->setSumPriceToPayAggregation($expenseEntity->getPriceToPayAggregation());
        $expenseTransfer->setSumTaxAmount($expenseEntity->getTaxAmount());

        $expenseTransfer->setIsOrdered(true);
        $this->deriveExpenseUnitPrices($expenseTransfer);

        return $expenseTransfer;
    }
}
