<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\Order;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Panth\OrderCleanup\Controller\Adminhtml\Order\Delete;
use Panth\OrderCleanup\Model\OrderDeleter;
use Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class DeleteTest extends ControllerTestCase
{
    private const TYPE_ID = 'panth_order_cleanup/safety/require_type_order_id';

    /** @var array<int, array{0: int, 1: string}> */
    private array $deleterCalls = [];

    private function controller(
        array $params,
        array $config = [],
        array $result = ['success' => true, 'message' => 'Order #100 deleted.'],
        ?OrderRepositoryInterface $repository = null
    ): Delete {
        $this->deleterCalls = [];
        $deleter = $this->createStub(OrderDeleter::class);
        $deleter->method('deleteOrder')->willReturnCallback(function ($id, $method) use ($result) {
            $this->deleterCalls[] = [$id, $method];
            return $result;
        });

        return new Delete(
            $this->context($params),
            $deleter,
            $this->scopeConfig($config),
            $repository ?? $this->repository('100')
        );
    }

    private function repository(?string $incrementId, bool $missing = false): OrderRepositoryInterface
    {
        $repository = $this->createStub(OrderRepositoryInterface::class);
        if ($missing) {
            $repository->method('get')->willThrowException(new NoSuchEntityException(__('gone')));
            return $repository;
        }
        $order = $this->createStub(OrderInterface::class);
        $order->method('getIncrementId')->willReturn($incrementId);
        $repository->method('get')->willReturn($order);
        return $repository;
    }

    public function testMissingOrderIdRedirectsToGridWithError(): void
    {
        $result = $this->controller([])->execute();

        $this->assertSame($this->response, $result);
        $this->assertSame('sales/order/index', $this->redirect['route']);
        $this->assertStringContainsString('No order was specified', $this->messages['error'][0]);
        $this->assertSame([], $this->deleterCalls);
    }

    public function testNonNumericOrderIdIsTreatedAsMissing(): void
    {
        $this->controller(['order_id' => 'abc'])->execute();

        $this->assertSame([], $this->deleterCalls);
        $this->assertCount(1, $this->messages['error']);
    }

    public function testSuccessfulDeletionWithoutTypedConfirmation(): void
    {
        $this->controller(['order_id' => '7'])->execute();

        $this->assertSame([[7, 'single']], $this->deleterCalls);
        $this->assertSame(['Order #100 deleted.'], $this->messages['success']);
        $this->assertSame([], $this->messages['error']);
        $this->assertSame('sales/order/index', $this->redirect['route']);
    }

    public function testDeleterFailureIsShownAsError(): void
    {
        $this->controller(['order_id' => '7'], [], ['success' => false, 'message' => 'Has invoices.'])->execute();

        $this->assertSame(['Has invoices.'], $this->messages['error']);
        $this->assertSame([], $this->messages['success']);
        $this->assertSame('sales/order/index', $this->redirect['route']);
    }

    public function testTypedConfirmationMatchingIncrementIdDeletes(): void
    {
        $this->controller(['order_id' => '7', 'confirm_increment_id' => '  100 '], [self::TYPE_ID => true])
            ->execute();

        $this->assertSame([[7, 'single']], $this->deleterCalls);
    }

    public function testTypedConfirmationMismatchRedirectsBackToOrderView(): void
    {
        $this->controller(['order_id' => '7', 'confirm_increment_id' => '101'], [self::TYPE_ID => true])
            ->execute();

        $this->assertSame([], $this->deleterCalls);
        $this->assertSame(['route' => 'sales/order/view', 'params' => ['order_id' => 7]], $this->redirect);
        $this->assertStringContainsString('Type the order number exactly', $this->messages['error'][0]);
    }

    public function testMissingTypedConfirmationIsRejected(): void
    {
        $this->controller(['order_id' => '7'], [self::TYPE_ID => true])->execute();

        $this->assertSame([], $this->deleterCalls);
        $this->assertSame('sales/order/view', $this->redirect['route']);
    }

    public function testEmptyIncrementIdCanNeverBeConfirmed(): void
    {
        $this->controller(
            ['order_id' => '7', 'confirm_increment_id' => ''],
            [self::TYPE_ID => true],
            ['success' => true, 'message' => 'x'],
            $this->repository(null)
        )->execute();

        $this->assertSame([], $this->deleterCalls);
        $this->assertSame('sales/order/view', $this->redirect['route']);
    }

    public function testTypedConfirmationForUnknownOrderRedirectsToGrid(): void
    {
        $this->controller(
            ['order_id' => '7', 'confirm_increment_id' => '100'],
            [self::TYPE_ID => true],
            ['success' => true, 'message' => 'x'],
            $this->repository(null, true)
        )->execute();

        $this->assertSame([], $this->deleterCalls);
        $this->assertSame('sales/order/index', $this->redirect['route']);
        $this->assertStringContainsString('could not be found', $this->messages['error'][0]);
    }

    public function testAdminResourceConstant(): void
    {
        $this->assertSame('Panth_OrderCleanup::delete_order', Delete::ADMIN_RESOURCE);
    }
}
