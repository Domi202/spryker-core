<?php

/**
 * MIT License
 * For full license information, please view the LICENSE file that was distributed with this source code.
 */

namespace Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Business\Model\DataImportStep;

use Generated\Shared\Transfer\CurrencyTransfer;
use Generated\Shared\Transfer\MerchantRelationshipMinimumOrderValueTransfer;
use Generated\Shared\Transfer\MerchantRelationshipTransfer;
use Generated\Shared\Transfer\MinimumOrderValueTypeTransfer;
use Generated\Shared\Transfer\StoreTransfer;
use Spryker\Zed\DataImport\Business\Model\DataImportStep\DataImportStepInterface;
use Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface;
use Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Business\Model\DataSet\MerchantRelationshipMinimumOrderValueDataSetInterface;
use Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToCurrencyFacadeInterface;
use Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipFacadeInterface;
use Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipMinimumOrderValueFacadeInterface;
use Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToStoreFacadeInterface;

class MerchantRelationshipMinimumOrderValueWriterStep implements DataImportStepInterface
{
    protected const MERCHANT_RELATIONSHIPS_HEAP_LIMIT = 200;

    /**
     * @var \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipMinimumOrderValueFacadeInterface
     */
    protected $merchantRelationshipMinimumOrderValueFacade;

    /**
     * @var \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipFacadeInterface
     */
    protected $merchantRelationshipFacade;

    /**
     * @var \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToStoreFacadeInterface
     */
    protected $storeFacade;

    /**
     * @var \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToCurrencyFacadeInterface
     */
    protected $currencyFacade;

    /**
     * @var \Generated\Shared\Transfer\MerchantRelationshipTransfer[]
     */
    protected static $merchantRelationshipsHeap = [];

    /**
     * @var int
     */
    protected static $merchantRelationshipsHeapSize = 0;

    /**
     * @var \Generated\Shared\Transfer\StoreTransfer[]
     */
    protected static $storesHeap = [];

    /**
     * @var \Generated\Shared\Transfer\CurrencyTransfer[]
     */
    protected static $currenciesHeap = [];

    /**
     * @param \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipMinimumOrderValueFacadeInterface $merchantRelationshipMinimumOrderValueFacade
     * @param \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipFacadeInterface $merchantRelationshipFacade
     * @param \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToStoreFacadeInterface $storeFacade
     * @param \Spryker\Zed\MerchantRelationshipMinimumOrderValueDataImport\Dependency\Facade\MerchantRelationshipMinimumOrderValueDataImportToCurrencyFacadeInterface $currencyFacade
     */
    public function __construct(
        MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipMinimumOrderValueFacadeInterface $merchantRelationshipMinimumOrderValueFacade,
        MerchantRelationshipMinimumOrderValueDataImportToMerchantRelationshipFacadeInterface $merchantRelationshipFacade,
        MerchantRelationshipMinimumOrderValueDataImportToStoreFacadeInterface $storeFacade,
        MerchantRelationshipMinimumOrderValueDataImportToCurrencyFacadeInterface $currencyFacade
    ) {
        $this->merchantRelationshipMinimumOrderValueFacade = $merchantRelationshipMinimumOrderValueFacade;
        $this->merchantRelationshipFacade = $merchantRelationshipFacade;
        $this->storeFacade = $storeFacade;
        $this->currencyFacade = $currencyFacade;
    }

    /**
     * @param \Spryker\Zed\DataImport\Business\Model\DataSet\DataSetInterface $dataSet
     *
     * @return void
     */
    public function execute(DataSetInterface $dataSet): void
    {
        $merchantRelationshipTransfer = $this->getMerchantRelationshipByKey(
            $dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::MERCHANT_RELATIONSHIP_KEY]
        );

        $storeTransfer = $this->findStoreByName($dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::STORE]);
        if (!$storeTransfer) {
            return;
        }

        $currencyTransfer = $this->findCurrencyByCode($dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::CURRENCY]);
        if (!$currencyTransfer) {
            return;
        }

        if ($dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::STRATEGY] && $dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::THRESHOLD]) {
            $merchantRelationshipMinimumOrderValueTransfer = $this->createMerchantRelationshipMinimumOrderValueTransfer(
                $dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::STRATEGY],
                $merchantRelationshipTransfer,
                $storeTransfer,
                $currencyTransfer,
                (int)$dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::THRESHOLD],
                (int)$dataSet[MerchantRelationshipMinimumOrderValueDataSetInterface::FEE]
            );

            $this->merchantRelationshipMinimumOrderValueFacade->setMerchantRelationshipThreshold(
                $merchantRelationshipMinimumOrderValueTransfer
            );
        }
    }

    /**
     * @param string $strategyKey
     * @param \Generated\Shared\Transfer\MerchantRelationshipTransfer $merchantRelationshipTransfer
     * @param \Generated\Shared\Transfer\StoreTransfer $storeTransfer
     * @param \Generated\Shared\Transfer\CurrencyTransfer $currencyTransfer
     * @param int $thresholdValue
     * @param int|null $fee
     *
     * @return \Generated\Shared\Transfer\MerchantRelationshipMinimumOrderValueTransfer
     */
    protected function createMerchantRelationshipMinimumOrderValueTransfer(
        string $strategyKey,
        MerchantRelationshipTransfer $merchantRelationshipTransfer,
        StoreTransfer $storeTransfer,
        CurrencyTransfer $currencyTransfer,
        int $thresholdValue,
        ?int $fee = null
    ): MerchantRelationshipMinimumOrderValueTransfer {
        return (new MerchantRelationshipMinimumOrderValueTransfer())
            ->setMerchantRelationship($merchantRelationshipTransfer)
            ->setStore($storeTransfer)
            ->setCurrency($currencyTransfer)
            ->setValue($thresholdValue)
            ->setFee($fee)
            ->setMinimumOrderValueType(
                (new MinimumOrderValueTypeTransfer())
                    ->setKey($strategyKey)
            );
    }

    /**
     * @param string $merchantRelationshipKey
     *
     * @return \Generated\Shared\Transfer\MerchantRelationshipTransfer
     */
    protected function getMerchantRelationshipByKey(string $merchantRelationshipKey): MerchantRelationshipTransfer
    {
        if (static::$merchantRelationshipsHeapSize > static::MERCHANT_RELATIONSHIPS_HEAP_LIMIT) {
            static::$merchantRelationshipsHeap = [];
        }

        if (!isset(static::$merchantRelationshipsHeap[$merchantRelationshipKey])) {
            static::$merchantRelationshipsHeap[$merchantRelationshipKey] = $this->merchantRelationshipFacade->getMerchantRelationshipByKey(
                (new MerchantRelationshipTransfer())->setMerchantRelationshipKey($merchantRelationshipKey)
            );

            static::$merchantRelationshipsHeapSize++;
        }

        return static::$merchantRelationshipsHeap[$merchantRelationshipKey];
    }

    /**
     * @param string $storeName
     *
     * @return \Generated\Shared\Transfer\StoreTransfer
     */
    protected function findStoreByName(string $storeName): StoreTransfer
    {
        if (!isset(static::$storesHeap[$storeName])) {
            static::$storesHeap[$storeName] = $this->storeFacade->getStoreByName($storeName);
        }

        return static::$storesHeap[$storeName];
    }

    /**
     * @param string $isoCode
     *
     * @return \Generated\Shared\Transfer\CurrencyTransfer
     */
    protected function findCurrencyByCode(string $isoCode): CurrencyTransfer
    {
        if (!isset(static::$currenciesHeap[$isoCode])) {
            static::$currenciesHeap[$isoCode] = $this->currencyFacade->fromIsoCode($isoCode);
        }

        return static::$currenciesHeap[$isoCode];
    }
}
