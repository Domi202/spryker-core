<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductPackagingUnitStorage\Persistence;

use Generated\Shared\Transfer\FilterTransfer;

interface ProductPackagingUnitStorageRepositoryInterface
{
    /**
     * @deprecated Use getProductAbstractPackagingStorageCollectionByFilter instead.
     *
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\SpyProductAbstractPackagingStorageEntityTransfer[]
     */
    public function findProductAbstractPackagingStorageEntitiesByProductAbstractIds(array $productAbstractIds): array;

    /**
     * @param int $idProductAbstract
     *
     * @return \Generated\Shared\Transfer\SpyProductEntityTransfer[]
     */
    public function findPackagingProductsByProductAbstractId(int $idProductAbstract): array;

    /**
     * @deprecated Use getProductAbstractPackagingStorageCollectionByFilter instead.
     *
     * @return \Generated\Shared\Transfer\SpyProductAbstractPackagingStorageEntityTransfer[]
     */
    public function findAllProductAbstractPackagingStorageEntities(): array;

    /**
     * @deprecated Use getProductPackagingLeadProductCollectionByFilter instead.
     *
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\SpyProductPackagingLeadProductEntityTransfer[]
     */
    public function getProductPackagingLeadProductEntityTransfersByProductAbstractIds(array $productAbstractIds): array;

    /**
     * @param \Generated\Shared\Transfer\FilterTransfer $filterTransfer
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\SpyProductAbstractPackagingStorageEntityTransfer[]
     */
    public function findFilteredProductAbstractPackagingUnitStorages(FilterTransfer $filterTransfer, array $productAbstractIds = []): array;

    /**
     * @param \Generated\Shared\Transfer\FilterTransfer $filterTransfer
     *
     * @return \Generated\Shared\Transfer\SpyProductPackagingLeadProductEntityTransfer[]
     */
    public function getProductPackagingLeadProductCollectionByFilter(FilterTransfer $filterTransfer): array;

    /**
     * @param \Generated\Shared\Transfer\FilterTransfer $filterTransfer
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\SpyProductAbstractPackagingStorageEntityTransfer[]
     */
    public function getProductAbstractPackagingStorageCollectionByFilter(FilterTransfer $filterTransfer, array $productAbstractIds): array;
}
