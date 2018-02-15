<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Company\Persistence;

use Generated\Shared\Transfer\CompanyTransfer;

interface CompanyWriterRepositoryInterface
{
    /**
     * Specification:
     * - Creates a company
     * - Finds a company by CompanyTransfer::idCompany in the transfer
     * - Updates fields in a company entity
     * - Updates relation to stores
     *
     * @param \Generated\Shared\Transfer\CompanyTransfer $companyTransfer
     *
     * @return \Generated\Shared\Transfer\CompanyTransfer
     */
    public function save(CompanyTransfer $companyTransfer): CompanyTransfer;

    /**
     * Specification:
     * - Finds a company by CompanyTransfer::idCompany in the transfer
     * - Deletes the company
     *
     * @param \Generated\Shared\Transfer\CompanyTransfer $companyTransfer
     *
     * @return void
     */
    public function delete(CompanyTransfer $companyTransfer): void;

    /**
     * Specification:
     * - Adds new relations between stores and company
     *
     * @param array $idStores
     * @param int $idCompany
     *
     * @return void
     */
    public function addStores(array $idStores, $idCompany): void;

    /**
     * Specification:
     * - Remove relations between stores and company
     *
     * @param array $idStores
     * @param int $idCompany
     *
     * @return void
     */
    public function removeStores(array $idStores, $idCompany): void;
}