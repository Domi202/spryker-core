<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\CategoryPageSearch\Business;

interface CategoryPageSearchFacadeInterface
{
    /**
     * Specification:
     * - Queries all category nodes with these ids
     * - Creates a data structure tree
     * - Stores data as json encoded to search table
     * - Sends a copy of data to the queue.
     *
     * @api
     *
     * @param array $categoryNodeIds
     *
     * @return void
     */
    public function publish(array $categoryNodeIds);

    /**
     * Specification:
     * - Finds and deletes category node search entities based on these ids
     * - Sends a copy of data to the queue.
     *
     * @api
     *
     * @param array $categoryNodeIds
     *
     * @return void
     */
    public function unpublish(array $categoryNodeIds);

    /**
     * Specification:
     * - Extracts category store IDs from the $eventTransfers created by category store entity events.
     * - Finds all category node IDs related to category store IDs.
     * - Queries all category nodes with these ids.
     * - Creates a data structure tree.
     * - Stores data as json encoded to search table.
     * - Sends a copy of data to the queue.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventEntityTransfers
     *
     * @return void
     */
    public function writeCategoryNodePageSearchCollectionByCategoryStoreEvents(array $eventEntityTransfers): void;

    /**
     * Specification:
     * - Extracts category store IDs from the $eventTransfers created by category store publish events.
     * - Finds all category node IDs related to category store IDs.
     * - Queries all category nodes with these ids.
     * - Creates a data structure tree.
     * - Stores data as json encoded to search table.
     * - Sends a copy of data to the queue.
     *
     * @api
     *
     * @param \Generated\Shared\Transfer\EventEntityTransfer[] $eventEntityTransfers
     *
     * @return void
     */
    public function writeCategoryNodePageSearchCollectionByCategoryStorePublishEvents(array $eventEntityTransfers): void;
}
