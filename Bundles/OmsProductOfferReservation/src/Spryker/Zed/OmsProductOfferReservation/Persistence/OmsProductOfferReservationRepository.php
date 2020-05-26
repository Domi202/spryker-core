<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Spryker Marketplace License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\OmsProductOfferReservation\Persistence;

use ArrayObject;
use Generated\Shared\Transfer\OmsProductOfferReservationCriteriaTransfer;
use Generated\Shared\Transfer\OmsProductOfferReservationTransfer;
use Generated\Shared\Transfer\ReservationRequestTransfer;
use Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderItemStateTableMap;
use Orm\Zed\Oms\Persistence\Map\SpyOmsOrderProcessTableMap;
use Orm\Zed\OmsProductOfferReservation\Persistence\Map\SpyOmsProductOfferReservationTableMap;
use Orm\Zed\OmsProductOfferReservation\Persistence\SpyOmsProductOfferReservationQuery;
use Orm\Zed\Sales\Persistence\Map\SpySalesOrderItemTableMap;
use Spryker\DecimalObject\Decimal;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;

/**
 * @method \Spryker\Zed\OmsProductOfferReservation\Persistence\OmsProductOfferReservationPersistenceFactory getFactory()
 */
class OmsProductOfferReservationRepository extends AbstractRepository implements OmsProductOfferReservationRepositoryInterface
{
    /**
     * @param \Generated\Shared\Transfer\ReservationRequestTransfer $reservationRequestTransfer
     *
     * @return \Generated\Shared\Transfer\OmsProductOfferReservationTransfer|null
     */
    public function find(
        ReservationRequestTransfer $reservationRequestTransfer
    ): ?OmsProductOfferReservationTransfer {
        $reservationRequestTransfer->requireProductOfferReference()
            ->requireReservationQuantity()
            ->requireStore()
            ->getStore()
            ->requireIdStore();

        $omsProductOfferReservationEntity = $this->getFactory()->getOmsProductOfferReservationPropelQuery()
            ->filterByProductOfferReference($reservationRequestTransfer->getProductOfferReference())
            ->filterByFkStore($reservationRequestTransfer->getStore()->getIdStore())
            ->findOne();

        if (!$omsProductOfferReservationEntity) {
            return null;
        }

        return $this->getFactory()
            ->createOmsProductOfferReservationMapper()
            ->mapOmsProductOfferReservationEntityToOmsProductOfferReservationTransfer(
                $omsProductOfferReservationEntity,
                new OmsProductOfferReservationTransfer()
            );
    }

    /**
     * @param \Generated\Shared\Transfer\OmsProductOfferReservationCriteriaTransfer $omsProductOfferReservationCriteriaTransfer
     *
     * @return \Spryker\DecimalObject\Decimal
     */
    public function getQuantity(
        OmsProductOfferReservationCriteriaTransfer $omsProductOfferReservationCriteriaTransfer
    ): Decimal {
        $omsProductOfferReservationQuery = $this->getFactory()
            ->getOmsProductOfferReservationPropelQuery()
            ->filterByProductOfferReference($omsProductOfferReservationCriteriaTransfer->getProductOfferReference());

        $omsProductOfferReservationQuery = $this->applyFilters(
            $omsProductOfferReservationQuery,
            $omsProductOfferReservationCriteriaTransfer
        );

        $quantity = $omsProductOfferReservationQuery
            ->select([SpyOmsProductOfferReservationTableMap::COL_RESERVATION_QUANTITY])
            ->findOne();

        if (!$quantity) {
            return new Decimal(0);
        }

        return new Decimal($quantity);
    }

    /**
     * @module Oms
     * @module Sales
     *
     * @param string $sku
     * @param \ArrayObject|\Generated\Shared\Transfer\OmsStateTransfer[] $omsStateTransfers
     * @param string|null $productOfferReference
     * @param \Generated\Shared\Transfer\StoreTransfer|null $storeTransfer
     *
     * @return \Generated\Shared\Transfer\SalesOrderItemStateAggregationTransfer[]
     */
    public function getAggregatedReservations(
        string $sku,
        ArrayObject $omsStateTransfers,
        ?string $productOfferReference = null,
        ?StoreTransfer $storeTransfer = null
    ): array {
        $salesOrderItemQuery = $this->getFactory()->getSalesOrderItemPropelQuery();
        $salesOrderItemQuery
            ->filterByProductOfferReference($productOfferReference)
            ->filterBySku($sku)
            ->groupByProductOfferReference()
            ->useStateQuery()
                ->filterByName_In(array_keys($omsStateTransfers->getArrayCopy()))
            ->endUse()
            ->groupByFkOmsOrderItemState()
            ->innerJoinProcess()
            ->groupByFkOmsOrderProcess()
            ->select([
                SpySalesOrderItemTableMap::COL_SKU,
            ])
            ->withColumn(SpySalesOrderItemTableMap::COL_SKU, SalesOrderItemStateAggregationTransfer::SKU)
            ->withColumn(SpyOmsOrderProcessTableMap::COL_NAME, SalesOrderItemStateAggregationTransfer::PROCESS_NAME)
            ->withColumn(SpyOmsOrderItemStateTableMap::COL_NAME, SalesOrderItemStateAggregationTransfer::STATE_NAME)
            ->withColumn('SUM(' . SpySalesOrderItemTableMap::COL_QUANTITY . ')', SalesOrderItemStateAggregationTransfer::SUM_AMOUNT);

        if ($storeTransfer !== null) {
            $salesOrderItemQuery
                ->useOrderQuery()
                    ->filterByStore($storeTransfer->getName())
                ->endUse();
        }

        $salesAggregationTransfers = [];
        /**
         * @var array $salesOrderItemAggregation
         */
        foreach ($salesOrderItemQuery->find() as $salesOrderItemAggregation) {
            $salesAggregationTransfers[] = (new SalesOrderItemStateAggregationTransfer())
                ->fromArray($salesOrderItemAggregation, true);
        }

        return $salesAggregationTransfers;
    }

    /**
     * @param \Orm\Zed\OmsProductOfferReservation\Persistence\SpyOmsProductOfferReservationQuery $omsProductOfferReservationQuery
     * @param \Generated\Shared\Transfer\OmsProductOfferReservationCriteriaTransfer $omsProductOfferReservationCriteriaTransfer
     *
     * @return \Orm\Zed\OmsProductOfferReservation\Persistence\SpyOmsProductOfferReservationQuery
     */
    protected function applyFilters(
        SpyOmsProductOfferReservationQuery $omsProductOfferReservationQuery,
        OmsProductOfferReservationCriteriaTransfer $omsProductOfferReservationCriteriaTransfer
    ): SpyOmsProductOfferReservationQuery {
        if ($omsProductOfferReservationCriteriaTransfer->getStore()->getIdStore()) {
            $omsProductOfferReservationQuery->filterByFkStore(
                $omsProductOfferReservationCriteriaTransfer->getStore()->getIdStore()
            );
        }

        if ($omsProductOfferReservationCriteriaTransfer->getStore()->getName()) {
            $omsProductOfferReservationQuery
                ->useStoreQuery()
                    ->filterByName($omsProductOfferReservationCriteriaTransfer->getStore()->getName())
                ->endUse();
        }

        return $omsProductOfferReservationQuery;
    }
}
