<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ShipmentDataImport\Business\ShipmentStore\Writer\Step;

use Orm\Zed\Shipment\Persistence\SpyShipmentMethodQuery;
use Spryker\Zed\DataImport\Business\Exception\EntityNotFoundException;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\ShipmentDataImport\Business\ShipmentStore\Writer\DataSet\ShipmentMethodStoreDataSetInterface;

class ShipmentMethodKeyToIdShipmentMethodStep implements DataImportStepInterface
{
    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @throws \Spryker\Zed\DataImport\Business\Exception\EntityNotFoundException
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $shipmentMethodKey = $dataSet[ShipmentMethodStoreDataSetInterface::COLUMN_SHIPMENT_METHOD_KEY];

        if (!$shipmentMethodKey) {
            throw new EntityNotFoundException(sprintf('Invalid shipment method key: %s', $shipmentMethodKey));
        }

        $shipmentMethodEntity = SpyShipmentMethodQuery::create()
            ->filterByShipmentMethodKey($shipmentMethodKey)
            ->findOne();

        if ($shipmentMethodEntity === null) {
            throw new EntityNotFoundException('Shipment method not found');
        }

        $dataSet[ShipmentMethodStoreDataSetInterface::COLUMN_ID_SHIPMENT_METHOD] = $shipmentMethodEntity->getIdShipmentMethod();
    }
}
