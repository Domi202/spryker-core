<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace Spryker\Zed\Wishlist\Business\Model;

use ArrayObject;
use Generated\Shared\Transfer\CustomerTransfer;
use Generated\Shared\Transfer\WishlistCollectionTransfer;
use Generated\Shared\Transfer\WishlistFilterTransfer;
use Generated\Shared\Transfer\WishlistItemMetaTransfer;
use Generated\Shared\Transfer\WishlistOverviewMetaTransfer;
use Generated\Shared\Transfer\WishlistOverviewRequestTransfer;
use Generated\Shared\Transfer\WishlistOverviewResponseTransfer;
use Generated\Shared\Transfer\WishlistPaginationTransfer;
use Generated\Shared\Transfer\WishlistResponseTransfer;
use Generated\Shared\Transfer\WishlistTransfer;
use Propel\Runtime\Util\PropelModelPager;
use Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException;
use Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface;
use Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface;
use Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface;

class Reader implements ReaderInterface
{
    protected const ERROR_MESSAGE_WISHLIST_NOT_FOUND = 'wishlist.not.found';

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface
     */
    protected $queryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface
     */
    protected $productQueryContainer;

    /**
     * @var \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface
     */
    protected $transferMapper;

    /**
     * @var \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface
     */
    protected $wishlistRepository;

    /**
     * @var \Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface[]
     */
    protected $wishlistReloadItemsPlugins;

    /**
     * @var array|\Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface[]
     */
    protected $wishlistItemsValidatorPlugins;

    /**
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistQueryContainerInterface $queryContainer
     * @param \Spryker\Zed\Wishlist\Dependency\QueryContainer\WishlistToProductInterface $productQueryContainer
     * @param \Spryker\Zed\Wishlist\Business\Transfer\WishlistTransferMapperInterface $transferMapper
     * @param \Spryker\Zed\Wishlist\Persistence\WishlistRepositoryInterface $wishlistRepository
     * @param \Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistReloadItemsPluginInterface[] $wishlistReloadItemsPlugins
     * @param \Spryker\Zed\WishlistExtension\Dependency\Plugin\WishlistItemsValidatorPluginInterface[] $wishlistItemsValidatorPlugins
     */
    public function __construct(
        WishlistQueryContainerInterface $queryContainer,
        WishlistToProductInterface $productQueryContainer,
        WishlistTransferMapperInterface $transferMapper,
        WishlistRepositoryInterface $wishlistRepository,
        array $wishlistReloadItemsPlugins = [],
        array $wishlistItemsValidatorPlugins = []
    ) {
        $this->queryContainer = $queryContainer;
        $this->productQueryContainer = $productQueryContainer;
        $this->transferMapper = $transferMapper;
        $this->wishlistRepository = $wishlistRepository;
        $this->wishlistReloadItemsPlugins = $wishlistReloadItemsPlugins;
        $this->wishlistItemsValidatorPlugins = $wishlistItemsValidatorPlugins;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    public function getWishlistByName(WishlistTransfer $wishlistTransfer)
    {
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();

        $wishlistEntity = $this->getWishlistEntityByCustomerIdAndWishlistName(
            $wishlistTransfer->getFkCustomer(),
            $wishlistTransfer->getName()
        );

        return $this->transferMapper->convertWishlist($wishlistEntity);
    }

    /**
     * @api
     *
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    public function getWishlistOverview(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $this->assertWishlistOverviewRequest($wishlistOverviewRequestTransfer);

        $wishlistPaginationTransfer = $this->buildWishlistPaginationTransfer($wishlistOverviewRequestTransfer);
        $wishlistOverviewResponseTransfer = $this->buildWishlistOverviewResponseTransfer(
            $wishlistOverviewRequestTransfer->getWishlist(),
            $wishlistPaginationTransfer
        );

        $wishlistEntity = $this->findWishlistEntity($wishlistOverviewRequestTransfer->getWishlist());
        if (!$wishlistEntity) {
            return $wishlistOverviewResponseTransfer;
        }

        $wishlistTransfer = $this->transferMapper->convertWishlist($wishlistEntity);
        $wishlistTransfer->fromArray($wishlistOverviewRequestTransfer->getWishlist()->modifiedToArray());
        $wishlistOverviewRequestTransfer->setWishlist($wishlistTransfer);

        $itemPaginationModel = $this->getWishlistOverviewPaginationModel($wishlistOverviewRequestTransfer);
        $wishlistPaginationTransfer = $this->updatePaginationTransfer($wishlistPaginationTransfer, $itemPaginationModel);
        /** @var \Orm\Zed\Wishlist\Persistence\SpyWishlistItem[]|\Propel\Runtime\Collection\ObjectCollection $wishlistItemCollection */
        $wishlistItemCollection = $itemPaginationModel->getResults();
        $wishlistItems = $this->transferMapper->convertWishlistItemCollection($wishlistItemCollection);

        $wishlistItems = $this->expandProductId($wishlistItems);
        $wishlistTransfer->setWishlistItems(new ArrayObject($wishlistItems));

        $wishlistTransfer = $this->reloadWishlistItems($wishlistTransfer);
        $this->validateWishlistItems($wishlistTransfer, $wishlistOverviewResponseTransfer);

        $wishlistOverviewMetaTransfer = $this->createWishlistOverviewMeta($wishlistOverviewRequestTransfer);

        $wishlistOverviewMetaTransfer->setWishlistItemMetaCollection(
            $this->transferMapper->mapWishlistItemTransfersToWishlistItemMetaTransfers(
                $wishlistTransfer->getWishlistItems(),
                $wishlistOverviewMetaTransfer->getWishlistItemMetaCollection()
            )
        );

        $wishlistOverviewResponseTransfer
            ->setWishlist($wishlistTransfer)
            ->setPagination($wishlistPaginationTransfer)
            ->setItems($wishlistTransfer->getWishlistItems())
            ->setMeta($wishlistOverviewMetaTransfer);

        return $wishlistOverviewResponseTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return void
     */
    protected function assertWishlistOverviewRequest(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();

        $wishlistTransfer = $wishlistOverviewRequestTransfer->getWishlist();
        $wishlistTransfer->requireFkCustomer();
        $wishlistTransfer->requireName();
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistPaginationTransfer
     */
    protected function buildWishlistPaginationTransfer(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        return (new WishlistPaginationTransfer())
            ->setPage($wishlistOverviewRequestTransfer->getPage())
            ->setItemsPerPage($wishlistOverviewRequestTransfer->getItemsPerPage());
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param \Generated\Shared\Transfer\WishlistPaginationTransfer $wishlistPaginationTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewResponseTransfer
     */
    protected function buildWishlistOverviewResponseTransfer(WishlistTransfer $wishlistTransfer, WishlistPaginationTransfer $wishlistPaginationTransfer)
    {
        return (new WishlistOverviewResponseTransfer())
            ->setWishlist($wishlistTransfer)
            ->setPagination($wishlistPaginationTransfer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist|null
     */
    protected function findWishlistEntity(WishlistTransfer $wishlistTransfer)
    {
        return $this->queryContainer
            ->queryWishlistByCustomerId($wishlistTransfer->getFkCustomer())
            ->filterByName($wishlistTransfer->getName())
            ->findOne();
    }

    /**
     * @phpstan-param \Propel\Runtime\Util\PropelModelPager<mixed> $itemPaginationModel
     *
     * @param \Generated\Shared\Transfer\WishlistPaginationTransfer $paginationTransfer
     * @param \Propel\Runtime\Util\PropelModelPager $itemPaginationModel
     *
     * @return \Generated\Shared\Transfer\WishlistPaginationTransfer
     */
    protected function updatePaginationTransfer(WishlistPaginationTransfer $paginationTransfer, PropelModelPager $itemPaginationModel)
    {
        $pagesTotal = ceil($itemPaginationModel->getNbResults() / $itemPaginationModel->getMaxPerPage());
        $paginationTransfer->setPagesTotal($pagesTotal);
        $paginationTransfer->setItemsTotal($itemPaginationModel->getNbResults());
        $paginationTransfer->setItemsPerPage($itemPaginationModel->getMaxPerPage());

        if ($paginationTransfer->getPage() <= 0) {
            $paginationTransfer->setPage(1);
        }

        if ($paginationTransfer->getPage() > $pagesTotal) {
            $paginationTransfer->setPage($pagesTotal);
        }

        return $paginationTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlistItem[]|\Propel\Runtime\Util\PropelModelPager
     */
    protected function getWishlistOverviewPaginationModel(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewRequestTransfer->requireWishlist();
        $wishlistOverviewRequestTransfer->getWishlist()->requireIdWishlist();

        $page = $wishlistOverviewRequestTransfer
            ->requirePage()
            ->getPage();

        $maxPerPage = $wishlistOverviewRequestTransfer
            ->requireItemsPerPage()
            ->getItemsPerPage();

        $itemsQuery = $this->queryContainer->queryItemsByWishlistId(
            $wishlistOverviewRequestTransfer->getWishlist()->getIdWishlist()
        );

        return $itemsQuery->paginate($page, $maxPerPage);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistOverviewMetaTransfer
     */
    protected function createWishlistOverviewMeta(WishlistOverviewRequestTransfer $wishlistOverviewRequestTransfer)
    {
        $wishlistOverviewMetaTransfer = new WishlistOverviewMetaTransfer();
        $idCustomer = $wishlistOverviewRequestTransfer->getWishlist()->getFkCustomer();
        $idWishlist = $wishlistOverviewRequestTransfer->getWishlist()->getIdWishlist();

        $wishlistOverviewMetaTransfer
            ->setWishlistCollection($this->getCollectionByIdCustomer($idCustomer))
            ->setWishlistItemMetaCollection($this->createWishlistItemMetaCollection($idWishlist));

        return $wishlistOverviewMetaTransfer;
    }

    /**
     * @phpstan-return \ArrayObject<int, \Generated\Shared\Transfer\WishlistItemMetaTransfer>
     *
     * @param int $idWishlist
     *
     * @return \ArrayObject|\Generated\Shared\Transfer\WishlistItemMetaTransfer[]
     */
    protected function createWishlistItemMetaCollection($idWishlist)
    {
        $wishlistItemEntities = $this->queryContainer
            ->queryItemsByWishlistId($idWishlist)
            ->find();

        $wishlistItemMetaTransfers = new ArrayObject();
        foreach ($wishlistItemEntities as $wishlistItemEntity) {
            $productEntity = $wishlistItemEntity->getSpyProduct();
            $wishlistItemMetaTransfer = new WishlistItemMetaTransfer();
            $wishlistItemMetaTransfer->fromArray($wishlistItemEntity->toArray(), true);
            $wishlistItemMetaTransfer = $this->transferMapper
                ->mapProductEntityToWishlistItemMetaTransfer($productEntity, $wishlistItemMetaTransfer);
            $wishlistItemMetaTransfers->append($wishlistItemMetaTransfer);
        }

        return $wishlistItemMetaTransfers;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer[] $wishlistItemCollection
     *
     * @return \Generated\Shared\Transfer\WishlistItemTransfer[]
     */
    protected function expandProductId(array $wishlistItemCollection)
    {
        $productCollection = $this->getProductCollection($wishlistItemCollection);

        foreach ($productCollection as $productEntity) {
            foreach ($wishlistItemCollection as $itemTransfer) {
                if (mb_strtolower($itemTransfer->getSku()) === mb_strtolower($productEntity->getSku())) {
                    $itemTransfer->setIdProduct($productEntity->getIdProduct());
                }
            }
        }

        return $wishlistItemCollection;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistItemTransfer[] $itemCollection
     *
     * @return \Orm\Zed\Product\Persistence\SpyProduct[]|\Propel\Runtime\Collection\ObjectCollection
     */
    protected function getProductCollection(array $itemCollection)
    {
        $skuCollection = $this->getSkuCollection($itemCollection);

        return $this->productQueryContainer
            ->queryProduct()
            ->filterBySku_In($skuCollection)
            ->find();
    }

    /**
     * @phpstan-return array<int, string>
     *
     * @param \Generated\Shared\Transfer\WishlistItemTransfer[] $itemCollection
     *
     * @return array
     */
    protected function getSkuCollection(array $itemCollection)
    {
        $skuCollection = [];
        foreach ($itemCollection as $itemTransfer) {
            $skuCollection[] = $itemTransfer->getSku();
        }

        return $skuCollection;
    }

    /**
     * @param int $idWishlist
     *
     * @return int
     */
    protected function getTotalItemCount($idWishlist)
    {
        return $this->queryContainer->queryWishlistItem()
            ->filterByFkWishlist($idWishlist)
            ->count();
    }

    /**
     * @param int $idWishlist
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityById($idWishlist)
    {
        $wishlistEntity = $this->queryContainer->queryWishlist()
            ->filterByIdWishlist($idWishlist)
            ->findOne();

        if (!$wishlistEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist with id %s not found',
                $idWishlist
            ));
        }

        return $wishlistEntity;
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @throws \Spryker\Zed\Wishlist\Business\Exception\MissingWishlistException
     *
     * @return \Orm\Zed\Wishlist\Persistence\SpyWishlist
     */
    public function getWishlistEntityByCustomerIdAndWishlistName($idCustomer, $name)
    {
        $wishlistEntity = $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->findOne();

        if (!$wishlistEntity) {
            throw new MissingWishlistException(sprintf(
                'Wishlist: %s for customer with id: %s not found',
                $name,
                $idCustomer
            ));
        }

        return $wishlistEntity;
    }

    /**
     * @param \Generated\Shared\Transfer\CustomerTransfer $customerTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    public function getCustomerWishlistCollection(CustomerTransfer $customerTransfer)
    {
        if ($customerTransfer->getCustomerReference()) {
            return $this->getCollectionByCustomerReference($customerTransfer->getCustomerReference());
        }

        $idCustomer = $customerTransfer
            ->requireIdCustomer()
            ->getIdCustomer();

        return $this->getCollectionByIdCustomer($idCustomer);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistFilterTransfer $wishlistFilterTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistResponseTransfer
     */
    public function getWishlistByFilter(WishlistFilterTransfer $wishlistFilterTransfer): WishlistResponseTransfer
    {
        $wishlistFilterTransfer->requireIdCustomer();

        $wishlistTransfer = $this->wishlistRepository->findWishlistByFilter($wishlistFilterTransfer);

        if (!$wishlistTransfer) {
            return (new WishlistResponseTransfer())
                ->setIsSuccess(false)
                ->addError(static::ERROR_MESSAGE_WISHLIST_NOT_FOUND);
        }

        return (new WishlistResponseTransfer())
            ->setWishlist($wishlistTransfer)
            ->setIsSuccess(true);
    }

    /**
     * @param int $idCustomer
     * @param string $name
     *
     * @return bool
     */
    protected function hasCustomerWishlist($idCustomer, $name)
    {
        return $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->filterByName($name)
            ->count() > 0;
    }

    /**
     * @param int $idCustomer
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    protected function getCollectionByIdCustomer($idCustomer)
    {
        $wishlistCollection = new WishlistCollectionTransfer();
        $wishlistEntities = $this->queryContainer
            ->queryWishlistByCustomerId($idCustomer)
            ->find();

        if (!$wishlistEntities->count()) {
            return $wishlistCollection;
        }

        foreach ($wishlistEntities as $wishlistEntity) {
            $wishlistCollection->addWishlist($this->transferMapper->convertWishlist($wishlistEntity));
        }

        return $wishlistCollection;
    }

    /**
     * @param string $customerReference
     *
     * @return \Generated\Shared\Transfer\WishlistCollectionTransfer
     */
    protected function getCollectionByCustomerReference(string $customerReference): WishlistCollectionTransfer
    {
        return $this->wishlistRepository->getByCustomerReference($customerReference);
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     *
     * @return \Generated\Shared\Transfer\WishlistTransfer
     */
    protected function reloadWishlistItems(WishlistTransfer $wishlistTransfer): WishlistTransfer
    {
        foreach ($this->wishlistReloadItemsPlugins as $wishlistReloadItemsPlugin) {
            if ($wishlistReloadItemsPlugin->isApplicable($wishlistTransfer)) {
                $wishlistTransfer = $wishlistReloadItemsPlugin->reloadItems($wishlistTransfer);
            }
        }

        return $wishlistTransfer;
    }

    /**
     * @param \Generated\Shared\Transfer\WishlistTransfer $wishlistTransfer
     * @param \Generated\Shared\Transfer\WishlistOverviewResponseTransfer $wishlistOverviewResponseTransfer
     *
     * @return void
     */
    protected function validateWishlistItems(
        WishlistTransfer $wishlistTransfer,
        WishlistOverviewResponseTransfer $wishlistOverviewResponseTransfer
    ): void {
        foreach ($this->wishlistItemsValidatorPlugins as $wishlistItemsValidatorPlugin) {
            if (!$wishlistItemsValidatorPlugin->isApplicable($wishlistTransfer)) {
                continue;
            }

            $validationResponseTransfer = $wishlistItemsValidatorPlugin->validateItems($wishlistTransfer);

            if ($validationResponseTransfer->getIsSuccess()) {
                continue;
            }

            foreach ($validationResponseTransfer->getErrorMessages() as $messageTransfer) {
                $wishlistOverviewResponseTransfer->addError($messageTransfer);
            }
        }
    }
}
