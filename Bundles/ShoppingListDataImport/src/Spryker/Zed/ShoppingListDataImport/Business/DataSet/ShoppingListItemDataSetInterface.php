<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\ShoppingListDataImport\Business\DataSet;

interface ShoppingListItemDataSetInterface
{
    public const COLUMN_SHOPPING_LIST_KEY = 'shopping_list_key';
    public const COLUMN_PRODUCT_SKU = 'product_sku';
    public const COLUMN_QUANTITY = 'quantity';

    public const ID_SHOPPING_LIST = 'id_shopping_list';
    public const ID_COMPANY_USER = 'id_company_user';
}
