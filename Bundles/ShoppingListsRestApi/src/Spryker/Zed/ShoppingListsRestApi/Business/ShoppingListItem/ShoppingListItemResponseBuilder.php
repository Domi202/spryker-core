<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ShoppingListsRestApi\Business\ShoppingListItem;

use Generated\Shared\Transfer\RestErrorMessageTransfer;
use Generated\Shared\Transfer\RestShoppingListItemResponseTransfer;
use Spryker\Shared\ShoppingListsRestApi\ShoppingListsRestApiConfig;
use Symfony\Component\HttpFoundation\Response;

class ShoppingListItemResponseBuilder implements ShoppingListItemResponseBuilderInterface
{
    /**
     * @return \Generated\Shared\Transfer\RestShoppingListItemResponseTransfer
     */
    public function createRestShoppingListItemResponseTransfer(): RestShoppingListItemResponseTransfer
    {
        return new RestShoppingListItemResponseTransfer();
    }

    /**
     * @return \Generated\Shared\Transfer\RestShoppingListItemResponseTransfer
     */
    public function createShoppingListNotFoundErrorResponse(): RestShoppingListItemResponseTransfer
    {
        return $this->createRestShoppingListItemResponseTransfer()
            ->setIsSuccess(false)
            ->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_NOT_FOUND)
                    ->setCode(ShoppingListsRestApiConfig::RESPONSE_CODE_SHOPPING_LIST_NOT_FOUND)
                    ->setDetail(ShoppingListsRestApiConfig::RESPONSE_DETAIL_SHOPPING_LIST_NOT_FOUND)
            );
    }

    /**
     * @return \Generated\Shared\Transfer\RestShoppingListItemResponseTransfer
     */
    public function createCompanyUserNotFoundErrorResponse(): RestShoppingListItemResponseTransfer
    {
        return $this->createRestShoppingListItemResponseTransfer()
            ->setIsSuccess(false)
            ->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_FORBIDDEN)
                    ->setCode(ShoppingListsRestApiConfig::RESPONSE_CODE_COMPANY_USER_NOT_FOUND)
                    ->setDetail(ShoppingListsRestApiConfig::RESPONSE_DETAIL_COMPANY_USER_NOT_FOUND)
            );
    }

    /**
     * @return \Generated\Shared\Transfer\RestShoppingListItemResponseTransfer
     */
    public function createShoppingListCanNotAddItemErrorResponse(): RestShoppingListItemResponseTransfer
    {
        return $this->createRestShoppingListItemResponseTransfer()
            ->setIsSuccess(false)
            ->addError(
                (new RestErrorMessageTransfer())
                    ->setStatus(Response::HTTP_UNPROCESSABLE_ENTITY)
                    ->setCode(ShoppingListsRestApiConfig::RESPONSE_CODE_SHOPPING_LIST_CANNOT_ADD_ITEM)
                    ->setDetail(ShoppingListsRestApiConfig::RESPONSE_DETAIL_SHOPPING_LIST_CANNOT_ADD_ITEM)
            );
    }
}
