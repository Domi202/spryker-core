<?php

/**
 * Copyright © 2016-present Spryker Systems GmbH. All rights reserved.
 * Use of this software requires acceptance of the Evaluation License Agreement. See LICENSE file.
 */

namespace SprykerTest\Zed\Sales\Business\Facade;

use Codeception\TestCase\Test;
use Generated\Shared\Transfer\OrderItemFilterTransfer;
use Spryker\Zed\Sales\SalesDependencyProvider;
use Spryker\Zed\SalesExtension\Dependency\Plugin\OrderItemExpanderPluginInterface;

/**
 * Auto-generated group annotations
 *
 * @group SprykerTest
 * @group Zed
 * @group Sales
 * @group Business
 * @group Facade
 * @group GetOrderItemsTest
 * Add your own group annotations below this line
 */
class GetOrderItemsTest extends Test
{
    protected const DEFAULT_OMS_PROCESS_NAME = 'Test01';
    protected const FAKE_OMS_STATE = 'FAKE_OMS_STATE';
    protected const FAKE_ID_SALES_ORDER_ITEM = 6666;

    /**
     * @var \SprykerTest\Zed\Sales\SalesBusinessTester
     */
    protected $tester;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->tester->configureTestStateMachine([static::DEFAULT_OMS_PROCESS_NAME]);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsRetrieveOrderItemsByOrderItemIds(): void
    {
        // Arrange
        $idSalesOrderItem = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME)
            ->getOrderItems()
            ->getIterator()
            ->current()
            ->getIdSalesOrderItem();

        $orderItemFilterTransfer = (new OrderItemFilterTransfer())
            ->addSalesOrderItemId($idSalesOrderItem);

        // Act
        $itemTransfers = $this->tester
            ->getFacade()
            ->getOrderItems($orderItemFilterTransfer)
            ->getItems();

        // Assert
        /** @var \Generated\Shared\Transfer\ItemTransfer $itemTransfer */
        $itemTransfer = $itemTransfers->getIterator()->current();

        $this->assertCount(1, $itemTransfers);
        $this->assertSame($idSalesOrderItem, $itemTransfer->getIdSalesOrderItem());
        $this->assertNotNull($itemTransfer->getState());
        $this->assertNotNull($itemTransfer->getProcess());
    }

    /**
     * @return void
     */
    public function testGetOrderItemsRetrieveOrderItemsByFakeOrderItemId(): void
    {
        // Arrange
        $idSalesOrderItem = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME)
            ->getOrderItems()
            ->getIterator()
            ->current()
            ->getIdSalesOrderItem();

        $orderItemFilterTransfer = (new OrderItemFilterTransfer())
            ->addSalesOrderItemId($idSalesOrderItem)
            ->addSalesOrderItemId(static::FAKE_ID_SALES_ORDER_ITEM);

        // Act
        $itemTransfers = $this->tester
            ->getFacade()
            ->getOrderItems($orderItemFilterTransfer)
            ->getItems();

        // Assert
        $this->assertCount(1, $itemTransfers);
    }

    /**
     * @return void
     */
    public function testGetOrderItemsRetrieveOrderItemsUsingExpanderPluginStack(): void
    {
        // Arrange
        $this->tester->setDependency(
            SalesDependencyProvider::PLUGINS_ORDER_ITEM_EXPANDER,
            [$this->getOrderItemExpanderPluginMock()]
        );

        $idSalesOrderItem = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME)
            ->getOrderItems()
            ->getIterator()
            ->current()
            ->getIdSalesOrderItem();

        $orderItemFilterTransfer = (new OrderItemFilterTransfer())
            ->addSalesOrderItemId($idSalesOrderItem);

        // Act
        $itemTransfers = $this->tester
            ->getFacade()
            ->getOrderItems($orderItemFilterTransfer)
            ->getItems();

        // Assert
        $this->assertSame(static::FAKE_ID_SALES_ORDER_ITEM, $itemTransfers->getIterator()->current()->getIdSalesOrderItem());
    }

    /**
     * @return void
     */
    public function testGetOrderItemsRetrieveOrderItemsWithHistoryStates(): void
    {
        // Arrange
        $idSalesOrderItem = $this->tester->haveOrder([], static::DEFAULT_OMS_PROCESS_NAME)
            ->getOrderItems()
            ->getIterator()
            ->current()
            ->getIdSalesOrderItem();

        $this->tester->setItemState($idSalesOrderItem, static::FAKE_OMS_STATE);

        $orderItemFilterTransfer = (new OrderItemFilterTransfer())
            ->addSalesOrderItemId($idSalesOrderItem);

        // Act
        $itemTransfers = $this->tester
            ->getFacade()
            ->getOrderItems($orderItemFilterTransfer)
            ->getItems();

        // Assert
        /** @var \Generated\Shared\Transfer\ItemTransfer $itemTransfer */
        $itemTransfer = $itemTransfers->getIterator()->current();

        $this->assertCount(2, $itemTransfer->getStateHistory());
        $this->assertSame(static::FAKE_OMS_STATE, $itemTransfer->getStateHistory()->offsetGet(1)->getName());
    }

    /**
     * @return \PHPUnit\Framework\MockObject\MockObject|\Spryker\Zed\SalesExtension\Dependency\Plugin\OrderItemExpanderPluginInterface
     */
    protected function getOrderItemExpanderPluginMock(): OrderItemExpanderPluginInterface
    {
        $orderItemExpanderPluginMock = $this
            ->getMockBuilder(OrderItemExpanderPluginInterface::class)
            ->getMock();

        $orderItemExpanderPluginMock
            ->method('expand')
            ->willReturnCallback(function (array $itemTransfers) {
                foreach ($itemTransfers as $itemTransfer) {
                    $itemTransfer->setIdSalesOrderItem(static::FAKE_ID_SALES_ORDER_ITEM);
                }

                return $itemTransfers;
            });

        return $orderItemExpanderPluginMock;
    }
}
