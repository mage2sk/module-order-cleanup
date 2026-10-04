<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\Order;

use Magento\Framework\DataObject;
use Magento\Sales\Model\ResourceModel\Order\Collection;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Magento\Ui\Component\MassAction\Filter;
use Panth\OrderCleanup\Controller\Adminhtml\Order\MassDelete;
use Panth\OrderCleanup\Model\OrderDeleter;
use Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class MassDeleteTest extends ControllerTestCase
{
    private const ENABLED = [
        'panth_order_cleanup/general/enabled' => true,
        'panth_order_cleanup/mass_action/enabled' => true,
    ];

    /** @var array<int, array{0: int, 1: string}> */
    private array $deleterCalls = [];

    /**
     * @param int[] $orderIds
     * @param array<int, array> $results keyed by order id
     */
    private function controller(
        array $config,
        array $orderIds = [],
        array $results = [],
        bool $filterFails = false
    ): MassDelete {
        $this->deleterCalls = [];

        $orders = array_map(static fn($id) => new DataObject(['entity_id' => (string)$id]), $orderIds);
        $collection = $this->createStub(Collection::class);
        $collection->method('getSize')->willReturn(count($orders));
        $collection->method('getIterator')->willReturn(new \ArrayIterator($orders));

        $factory = $this->createStub(CollectionFactory::class);
        $factory->method('create')->willReturn($collection);

        $filter = $this->createStub(Filter::class);
        if ($filterFails) {
            $filter->method('getCollection')->willThrowException(new \RuntimeException('bad selection'));
        } else {
            $filter->method('getCollection')->willReturn($collection);
        }

        $deleter = $this->createStub(OrderDeleter::class);
        $deleter->method('deleteOrder')->willReturnCallback(function ($id, $method) use ($results) {
            $this->deleterCalls[] = [$id, $method];
            return $results[$id] ?? ['success' => true, 'message' => 'ok'];
        });

        return new MassDelete($this->context(), $filter, $factory, $deleter, $this->scopeConfig($config));
    }

    public function testDisabledModuleIsRejected(): void
    {
        $this->controller(['panth_order_cleanup/mass_action/enabled' => true], [1])->execute();

        $this->assertStringContainsString('Order Cleanup is currently disabled', $this->messages['error'][0]);
        $this->assertSame([], $this->deleterCalls);
        $this->assertSame('sales/order/index', $this->redirect['route']);
    }

    public function testDisabledMassActionIsRejected(): void
    {
        $this->controller(['panth_order_cleanup/general/enabled' => true], [1])->execute();

        $this->assertStringContainsString('Mass order deletion is currently disabled', $this->messages['error'][0]);
        $this->assertSame([], $this->deleterCalls);
    }

    public function testSelectionFailureShowsError(): void
    {
        $this->controller(self::ENABLED, [], [], true)->execute();

        $this->assertSame(['Unable to load the selected orders. Please try again.'], $this->messages['error']);
        $this->assertSame([], $this->deleterCalls);
    }

    public function testSelectionAboveConfiguredLimitIsRejected(): void
    {
        $config = self::ENABLED + ['panth_order_cleanup/mass_action/max_orders_per_action' => '2'];

        $this->controller($config, [1, 2, 3])->execute();

        $this->assertStringContainsString('You selected 3 orders, but only 2', $this->messages['error'][0]);
        $this->assertSame([], $this->deleterCalls);
    }

    public function testSelectionEqualToLimitIsAllowed(): void
    {
        $config = self::ENABLED + ['panth_order_cleanup/mass_action/max_orders_per_action' => '2'];

        $this->controller($config, [1, 2])->execute();

        $this->assertSame([[1, 'mass'], [2, 'mass']], $this->deleterCalls);
    }

    public function testLimitDefaultsToFiftyWhenUnsetOrZero(): void
    {
        $this->controller(self::ENABLED, range(1, 51))->execute();
        $this->assertStringContainsString('only 50 orders', $this->messages['error'][0]);

        $config = self::ENABLED + ['panth_order_cleanup/mass_action/max_orders_per_action' => '0'];
        $this->controller($config, range(1, 50))->execute();
        $this->assertCount(50, $this->deleterCalls);
    }

    public function testSingleSuccessUsesSingularMessage(): void
    {
        $this->controller(self::ENABLED, [5])->execute();

        $this->assertSame(['1 order has been permanently deleted.'], $this->messages['success']);
        $this->assertSame([], $this->messages['error']);
        $this->assertSame('sales/order/index', $this->redirect['route']);
    }

    public function testMultipleSuccessesUsePluralMessage(): void
    {
        $this->controller(self::ENABLED, [5, 6, 7])->execute();

        $this->assertSame(['3 orders have been permanently deleted.'], $this->messages['success']);
    }

    public function testSingleFailureReportsItsReason(): void
    {
        $this->controller(self::ENABLED, [5, 6], [6 => ['success' => false, 'message' => 'Has invoices.']])
            ->execute();

        $this->assertSame(['1 order has been permanently deleted.'], $this->messages['success']);
        $this->assertSame(['1 order could not be deleted: Has invoices.'], $this->messages['error']);
    }

    public function testMultipleFailuresDeduplicateReasons(): void
    {
        $fail = ['success' => false, 'message' => 'Has invoices.'];
        $results = [5 => $fail, 6 => $fail, 7 => ['success' => false, 'message' => 'Not found.']];

        $this->controller(self::ENABLED, [5, 6, 7], $results)->execute();

        $this->assertSame([], $this->messages['success']);
        $this->assertSame(['3 orders could not be deleted: Has invoices. | Not found.'], $this->messages['error']);
    }

    public function testEmptySelectionAddsNoMessages(): void
    {
        $this->controller(self::ENABLED, [])->execute();

        $this->assertSame(['success' => [], 'error' => []], $this->messages);
        $this->assertSame('sales/order/index', $this->redirect['route']);
    }

    public function testAdminResourceConstant(): void
    {
        $this->assertSame('Panth_OrderCleanup::mass_delete', MassDelete::ADMIN_RESOURCE);
    }
}
