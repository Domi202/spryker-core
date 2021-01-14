<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\ProductCategoryStorage\Persistence;

use Orm\Zed\Category\Persistence\Map\SpyCategoryAttributeTableMap;
use Orm\Zed\Category\Persistence\Map\SpyCategoryClosureTableTableMap;
use Orm\Zed\Category\Persistence\Map\SpyCategoryNodeTableMap;
use Orm\Zed\Category\Persistence\Map\SpyCategoryStoreTableMap;
use Orm\Zed\Category\Persistence\Map\SpyCategoryTableMap;
use Orm\Zed\Locale\Persistence\Map\SpyLocaleTableMap;
use Orm\Zed\ProductCategory\Persistence\Map\SpyProductCategoryTableMap;
use Orm\Zed\ProductCategoryStorage\Persistence\Map\SpyProductAbstractCategoryStorageTableMap;
use Orm\Zed\Store\Persistence\Map\SpyStoreTableMap;
use Orm\Zed\Url\Persistence\Map\SpyUrlTableMap;
use Spryker\Zed\Kernel\Persistence\AbstractRepository;
use Spryker\Zed\PropelOrm\Business\Runtime\ActiveQuery\Criteria;

/**
 * @method \Spryker\Zed\ProductCategoryStorage\Persistence\ProductCategoryStoragePersistenceFactory getFactory()
 */
class ProductCategoryStorageRepository extends AbstractRepository implements ProductCategoryStorageRepositoryInterface
{
    protected const COL_ID_CATEGORY_NODE = 'id_category_node';
    protected const COL_FK_CATEGORY_NODE_DESCENDANT = 'fk_category_node_descendant';
    protected const COL_FK_CATEGORY = 'fk_category';
    protected const COL_NAME = 'name';
    protected const COL_URL = 'url';
    protected const COL_LOCALE = 'locale';
    protected const COL_STORE = 'store';

    /**
     * @return array
     */
    public function getAllCategoriesOrderedByDescendant(): array
    {
        $categoryNodeQuery = $this->getFactory()
            ->getCategoryNodePropelQuery()
            ->orderBy(SpyCategoryNodeTableMap::COL_NODE_ORDER, Criteria::DESC)
            ->addJoin(
                SpyCategoryNodeTableMap::COL_ID_CATEGORY_NODE,
                SpyUrlTableMap::COL_FK_RESOURCE_CATEGORYNODE,
                Criteria::LEFT_JOIN
            )
            ->where(SpyUrlTableMap::COL_FK_LOCALE . ' = ' . SpyCategoryAttributeTableMap::COL_FK_LOCALE);

        $categoryNodeQuery
            ->useClosureTableQuery()
                ->orderByFkCategoryNodeDescendant(Criteria::DESC)
                ->orderByDepth(Criteria::DESC)
                ->filterByDepth(null, Criteria::NOT_EQUAL)
            ->endUse()
            ->useCategoryQuery()
                ->useSpyCategoryStoreQuery(null, Criteria::LEFT_JOIN)
                    ->joinWithSpyStore()
                ->endUse()
                ->useAttributeQuery(null, Criteria::LEFT_JOIN)
                    ->joinWithLocale()
                ->endUse()
            ->endUse();

        $categoryNodeQuery->filterByIsRoot(false);

        $categoryNodeQuery
            ->withColumn(SpyCategoryNodeTableMap::COL_ID_CATEGORY_NODE, static::COL_ID_CATEGORY_NODE)
            ->withColumn(SpyCategoryClosureTableTableMap::COL_FK_CATEGORY_NODE_DESCENDANT, static::COL_FK_CATEGORY_NODE_DESCENDANT)
            ->withColumn(SpyCategoryNodeTableMap::COL_FK_CATEGORY, static::COL_FK_CATEGORY)
            ->withColumn(SpyCategoryAttributeTableMap::COL_NAME, static::COL_NAME)
            ->withColumn(SpyUrlTableMap::COL_URL, static::COL_URL)
            ->withColumn(SpyLocaleTableMap::COL_LOCALE_NAME, static::COL_LOCALE)
            ->withColumn(SpyStoreTableMap::COL_NAME, static::COL_STORE);

        $categoryNodeQuery->select([
            static::COL_ID_CATEGORY_NODE,
            static::COL_FK_CATEGORY_NODE_DESCENDANT,
            static::COL_FK_CATEGORY,
            static::COL_NAME,
            static::COL_URL,
            static::COL_LOCALE,
            static::COL_STORE,
        ]);

        return $categoryNodeQuery->find()->getData();
    }

    /**
     * @return int[]
     */
    public function getAllCategoryNodeIds(): array
    {
        return $this->getFactory()
            ->getCategoryNodePropelQuery()
            ->orderBy(SpyCategoryNodeTableMap::COL_NODE_ORDER, Criteria::DESC)
            ->select([SpyCategoryNodeTableMap::COL_ID_CATEGORY_NODE])
            ->find()
            ->toArray();
    }

    /**
     * @param int[] $productAbstractIds
     *
     * @return string[]
     */
    public function getProductAbstractCategoryStorageKeysByProductAbstractIds(array $productAbstractIds): array
    {
        return $this
            ->getFactory()
            ->createProductAbstractCategoryStoragePropelQuery()
            ->filterByFkProductAbstract_In($productAbstractIds)
            ->select([SpyProductAbstractCategoryStorageTableMap::COL_KEY])
            ->find()
            ->getData();
    }

    /**
     * @param int $idCategoryNode
     *
     * @return int[]
     */
    public function getAllCategoryIdsByCategoryNodeId(int $idCategoryNode): array
    {
        return $this->getFactory()
            ->getCategoryClosureTablePropelQuery()
            ->where(SpyCategoryClosureTableTableMap::COL_FK_CATEGORY_NODE . ' = ?', $idCategoryNode)
            ->_or()
            ->where(SpyCategoryClosureTableTableMap::COL_FK_CATEGORY_NODE_DESCENDANT . ' = ?', $idCategoryNode)
            ->joinDescendantNode()
            ->withColumn(SpyCategoryNodeTableMap::COL_FK_CATEGORY, static::COL_FK_CATEGORY)
            ->select([static::COL_FK_CATEGORY])
            ->find()
            ->getData();
    }

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductAbstractLocalizedAttributesTransfer[]
     */
    public function getProductAbstractLocalizedAttributes(array $productAbstractIds): array
    {
        $productAbstractLocalizedAttributesEntities = $this->getFactory()
            ->getProductAbstractLocalizedAttributesPropelQuery()
            ->joinWithLocale()
            ->filterByFkProductAbstract_In($productAbstractIds)
            ->find();

        return $this->getFactory()
            ->createProductAbstractLocalizedAttributesMapper()
            ->mapProductAbstractLocalizedAttributesEntitiesToProductAbstractLocalizedAttributesTransfers($productAbstractLocalizedAttributesEntities);
    }

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductCategoryTransfer[]
     */
    public function getProductCategoryWithCategoryNodes(array $productAbstractIds): array
    {
        $productCategoryQuery = $this->getFactory()
            ->getProductCategoryPropelQuery()
            ->filterByFkProductAbstract_In($productAbstractIds)
            ->innerJoinSpyCategory()
            ->addAnd(
                SpyCategoryTableMap::COL_IS_ACTIVE,
                true,
                Criteria::EQUAL
            )
            ->joinWithSpyCategory()
            ->joinWith('SpyCategory.Node')
            ->orderByProductOrder();

        return $this->getFactory()
            ->createProductCategoryMapper()
            ->mapProductCategoryEntitiesToProductCategoryTransfers($productCategoryQuery->find());
    }

    /**
     * @param int[] $productAbstractIds
     *
     * @return \Generated\Shared\Transfer\ProductAbstractCategoryStorageTransfer[][][]
     */
    public function getMappedProductAbstractCategoryStorages(array $productAbstractIds): array
    {
        $productAbstractCategoryStorageEntities = $this
            ->getFactory()
            ->createProductAbstractCategoryStoragePropelQuery()
            ->filterByFkProductAbstract_In($productAbstractIds)
            ->find();

        return $this->getFactory()
            ->createProductCategoryStorageMapper()
            ->mapProductAbstractCategoryStorageEntitiesToProductAbstractCategoryStorageTransfers($productAbstractCategoryStorageEntities);
    }

    /**
     * @param int[] $categoryIds
     *
     * @return int[]
     */
    public function getProductAbstractIdsByCategoryIds(array $categoryIds): array
    {
        return $this->getFactory()
            ->getProductCategoryPropelQuery()
            ->filterByFkCategory_In($categoryIds)
            ->select(SpyProductCategoryTableMap::COL_FK_PRODUCT_ABSTRACT)
            ->find()
            ->getData();
    }

    /**
     * @param int[] $categoryStoreIds
     *
     * @return int[]
     */
    public function getCategoryIdsByCategoryStoreIds(array $categoryStoreIds): array
    {
        return $this->getFactory()
            ->getCategoryStorePropelQuery()
            ->filterByIdCategoryStore_In($categoryStoreIds)
            ->select(SpyCategoryStoreTableMap::COL_FK_CATEGORY)
            ->find()
            ->getData();
    }
}
