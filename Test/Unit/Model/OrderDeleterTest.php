<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Model;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Item;
use Magento\User\Model\User;
use Panth\OrderCleanup\Model\OrderDeleter;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class OrderDeleterTest extends TestCase
{
    private const ORDER_ID = 42;

    /** @var array<string, mixed> */
    private array $config = [];

    /** @var array<int, array{0: string, 1: array}> */
    private array $deletes = [];

    /** @var array<int, array{0: string, 1: array}> */
    private array $inserts = [];

    /** @var string[] */
    private array $transaction = [];

    /** @var string[] */
    private array $events = [];

    /** @var array<string, string[]> */
    private array $logs = ['info' => [], 'error' => []];

    /** @var array<string, int> */
    private array $counts = [];

    /** @var array<string, array> */
    private array $columns = [];

    private ?\Throwable $deleteFailure = null;

    private ?User $adminUser = null;

    protected function setUp(): void
    {
        $this->config = [
            'panth_order_cleanup/general/enabled' => true,
            'panth_order_cleanup/safety/delete_invoices' => true,
            'panth_order_cleanup/safety/delete_shipments' => true,
            'panth_order_cleanup/safety/delete_creditmemos' => true,
            'panth_order_cleanup/safety/delete_transactions' => true,
            'panth_order_cleanup/safety/keep_deletion_log' => true,
        ];
    }

    private function order(array $data = []): Order
    {
        $data += [
            'increment_id' => '000000042',
            'status' => 'pending',
            'entity_id' => self::ORDER_ID,
            'quote_id' => 0,
            'customer_firstname' => 'Jane',
            'customer_lastname' => 'Doe',
            'customer_email' => 'jane@example.com',
            'grand_total' => '19.5',
            'order_currency_code' => 'EUR',
            'items' => [],
        ];
        $order = $this->createStub(Order::class);
        $order->method('getIncrementId')->willReturn($data['increment_id']);
        $order->method('getStatus')->willReturn($data['status']);
        $order->method('getEntityId')->willReturn($data['entity_id']);
        $order->method('getQuoteId')->willReturn($data['quote_id']);
        $order->method('getCustomerFirstname')->willReturn($data['customer_firstname']);
        $order->method('getCustomerLastname')->willReturn($data['customer_lastname']);
        $order->method('getCustomerEmail')->willReturn($data['customer_email']);
        $order->method('getGrandTotal')->willReturn($data['grand_total']);
        $order->method('getOrderCurrencyCode')->willReturn($data['order_currency_code']);
        $order->method('getAllVisibleItems')->willReturn($data['items']);
        return $order;
    }

    private function item(string $name, string $sku, $qty, $price): Item
    {
        $item = $this->createStub(Item::class);
        $item->method('getName')->willReturn($name);
        $item->method('getSku')->willReturn($sku);
        $item->method('getQtyOrdered')->willReturn($qty);
        $item->method('getPrice')->willReturn($price);
        return $item;
    }

    private function connection(): AdapterInterface
    {
        $tables = new \SplObjectStorage();
        $conn = $this->createStub(AdapterInterface::class);
        $conn->method('select')->willReturnCallback(function () use ($tables) {
            $select = $this->createStub(Select::class);
            $select->method('from')->willReturnCallback(
                static function ($table) use ($select, $tables) {
                    $tables[$select] = $table;
                    return $select;
                }
            );
            $select->method('where')->willReturn($select);
            return $select;
        });
        $conn->method('fetchOne')->willReturnCallback(
            fn($select) => (string)($this->counts[$tables[$select]] ?? 0)
        );
        $conn->method('fetchCol')->willReturnCallback(
            fn($select) => $this->columns[$tables[$select]] ?? []
        );
        $conn->method('delete')->willReturnCallback(function ($table, $where) {
            if ($this->deleteFailure !== null && $table === 'sales_order') {
                throw $this->deleteFailure;
            }
            $this->deletes[] = [$table, $where];
            return 1;
        });
        $conn->method('insert')->willReturnCallback(function ($table, $data) {
            $this->inserts[] = [$table, $data];
            return 1;
        });
        foreach (['beginTransaction', 'commit', 'rollBack'] as $method) {
            $conn->method($method)->willReturnCallback(function () use ($method, $conn) {
                $this->transaction[] = $method;
                return $conn;
            });
        }
        return $conn;
    }

    private function deleter(?Order $order = null, bool $orderMissing = false): OrderDeleter
    {
        $repository = $this->createStub(OrderRepositoryInterface::class);
        if ($orderMissing) {
            $repository->method('get')->willThrowException(new NoSuchEntityException(__('missing')));
        } else {
            $repository->method('get')->willReturn($order ?? $this->order());
        }

        $resource = $this->createStub(ResourceConnection::class);
        $resource->method('getConnection')->willReturn($this->connection());
        $resource->method('getTableName')->willReturnArgument(0);

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(
            fn($path) => (bool)($this->config[$path] ?? false)
        );
        $scopeConfig->method('getValue')->willReturnCallback(fn($path) => $this->config[$path] ?? null);

        $session = new class ($this->adminUser) extends AdminSession {
            /**
             * @param User|null $stubUser
             */
            public function __construct(private readonly ?User $stubUser)
            {
            }

            /**
             * @return User|null
             */
            public function getUser()
            {
                return $this->stubUser;
            }
        };

        $remote = $this->createStub(RemoteAddress::class);
        $remote->method('getRemoteAddress')->willReturn('203.0.113.7');

        $logger = $this->createStub(LoggerInterface::class);
        $logger->method('info')->willReturnCallback(function ($msg) {
            $this->logs['info'][] = $msg;
        });
        $logger->method('error')->willReturnCallback(function ($msg) {
            $this->logs['error'][] = $msg;
        });

        $events = $this->createStub(EventManager::class);
        $events->method('dispatch')->willReturnCallback(function ($name) {
            $this->events[] = $name;
        });

        return new OrderDeleter($repository, $resource, $scopeConfig, $session, $remote, $logger, $events);
    }

    private function deletedTables(): array
    {
        return array_map(static fn($d) => $d[0], $this->deletes);
    }

    public function testDisabledModuleRefusesWithoutTouchingTheDatabase(): void
    {
        $this->config['panth_order_cleanup/general/enabled'] = false;

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('disabled', $result['message']);
        $this->assertSame([], $this->transaction);
        $this->assertSame([], $this->deletes);
    }

    public function testMissingOrderReturnsNotFoundMessage(): void
    {
        $result = $this->deleter(null, true)->deleteOrder(self::ORDER_ID);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('could not be found', $result['message']);
        $this->assertSame([], $this->transaction);
    }

    public function testStatusOutsideAllowListIsRejected(): void
    {
        $this->config['panth_order_cleanup/safety/allowed_statuses'] = 'canceled, closed';

        $result = $this->deleter($this->order(['status' => 'processing']))->deleteOrder(self::ORDER_ID);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('"processing"', $result['message']);
        $this->assertStringContainsString('canceled, closed.', $result['message']);
        $this->assertSame([], $this->deletes);
    }

    public function testStatusInsideAllowListIsAcceptedAfterTrimming(): void
    {
        $this->config['panth_order_cleanup/safety/allowed_statuses'] = 'canceled , pending ';

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertTrue($result['success']);
    }

    public function testEmptyAllowListAllowsAnyStatus(): void
    {
        $this->config['panth_order_cleanup/safety/allowed_statuses'] = '';

        $result = $this->deleter($this->order(['status' => 'complete']))->deleteOrder(self::ORDER_ID);

        $this->assertTrue($result['success']);
    }

    #[DataProvider('relatedDocumentProvider')]
    public function testExistingRelatedDocumentBlocksDeletionWhenItsDeletionIsDisabled(
        string $setting,
        string $table,
        string $label
    ): void {
        $this->config['panth_order_cleanup/safety/' . $setting] = false;
        $this->counts[$table] = 2;

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('it has ' . $label, $result['message']);
        $this->assertSame([], $this->transaction);
        $this->assertSame([], $this->deletes);
    }

    public static function relatedDocumentProvider(): array
    {
        return [
            'invoices' => ['delete_invoices', 'sales_invoice', 'invoices'],
            'shipments' => ['delete_shipments', 'sales_shipment', 'shipments'],
            'credit memos' => ['delete_creditmemos', 'sales_creditmemo', 'credit memos'],
            'transactions' => ['delete_transactions', 'sales_payment_transaction', 'payment transactions'],
        ];
    }

    public function testDisabledRelatedDeletionWithNoDocumentsStillDeletesOrderButKeepsThoseTables(): void
    {
        foreach (['delete_invoices', 'delete_shipments', 'delete_creditmemos', 'delete_transactions'] as $s) {
            $this->config['panth_order_cleanup/safety/' . $s] = false;
        }

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertTrue($result['success']);
        $tables = $this->deletedTables();
        $this->assertContains('sales_order', $tables);
        $this->assertNotContains('sales_invoice', $tables);
        $this->assertNotContains('sales_payment_transaction', $tables);
    }

    public function testSuccessfulDeletionRemovesAllOrderTablesInsideOneTransaction(): void
    {
        $this->columns['sales_order_tax'] = [7, 8];

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertTrue($result['success']);
        $this->assertSame('000000042', $result['increment_id']);
        $this->assertStringContainsString('permanently deleted', $result['message']);
        $this->assertSame(['beginTransaction', 'commit'], $this->transaction);
        $this->assertSame(['sales_order_delete_before', 'sales_order_delete_after'], $this->events);

        $byTable = [];
        foreach ($this->deletes as [$table, $where]) {
            $byTable[$table] = $where;
        }
        $this->assertSame(['tax_id IN (?)' => [7, 8]], $byTable['sales_order_tax_item']);
        $this->assertSame(['order_id = ?' => self::ORDER_ID], $byTable['sales_order_item']);
        $this->assertSame(['parent_id = ?' => self::ORDER_ID], $byTable['sales_order_payment']);
        $this->assertSame(['parent_id = ?' => self::ORDER_ID], $byTable['sales_order_status_history']);
        $this->assertSame(['parent_id = ?' => self::ORDER_ID], $byTable['sales_order_address']);
        $this->assertSame(['entity_id = ?' => self::ORDER_ID], $byTable['sales_order_grid']);
        $this->assertSame(['entity_id = ?' => self::ORDER_ID], $byTable['sales_order']);
        $this->assertSame(['order_id = ?' => self::ORDER_ID], $byTable['sales_payment_transaction']);
    }

    public function testTaxItemsAreNotTouchedWhenOrderHasNoTaxRows(): void
    {
        $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertNotContains('sales_order_tax_item', $this->deletedTables());
        $this->assertContains('sales_order_tax', $this->deletedTables());
    }

    public function testRelatedDocumentsAreDeletedWithChildrenAndFlaggedInLog(): void
    {
        $this->columns['sales_invoice'] = [11, 12];
        $this->columns['sales_shipment'] = [21];

        $this->deleter()->deleteOrder(self::ORDER_ID);

        $deleted = $this->deletes;
        $this->assertContains(['sales_invoice_item', ['parent_id IN (?)' => [11, 12]]], $deleted);
        $this->assertContains(['sales_invoice_comment', ['parent_id IN (?)' => [11, 12]]], $deleted);
        $this->assertContains(['sales_invoice_grid', ['entity_id IN (?)' => [11, 12]]], $deleted);
        $this->assertContains(['sales_invoice', ['order_id = ?' => self::ORDER_ID]], $deleted);
        $this->assertContains(['sales_shipment_item', ['parent_id IN (?)' => [21]]], $deleted);
        $this->assertNotContains('sales_creditmemo', $this->deletedTables());

        $log = $this->inserts[0][1];
        $this->assertTrue($log['invoices_deleted']);
        $this->assertTrue($log['shipments_deleted']);
        $this->assertFalse($log['creditmemos_deleted']);
    }

    public function testQuoteAndItsChildrenAreDeletedWhenOrderHasAQuote(): void
    {
        $this->columns['quote_address'] = [5, 6];

        $this->deleter($this->order(['quote_id' => 99]))->deleteOrder(self::ORDER_ID);

        $this->assertContains(['quote_shipping_rate', ['address_id IN (?)' => [5, 6]]], $this->deletes);
        $this->assertContains(['quote_item', ['quote_id = ?' => 99]], $this->deletes);
        $this->assertContains(['quote_address', ['quote_id = ?' => 99]], $this->deletes);
        $this->assertContains(['quote_payment', ['quote_id = ?' => 99]], $this->deletes);
        $this->assertContains(['quote', ['entity_id = ?' => 99]], $this->deletes);
    }

    public function testQuoteTablesAreUntouchedWithoutQuote(): void
    {
        $this->deleter()->deleteOrder(self::ORDER_ID);

        foreach (['quote', 'quote_item', 'quote_address', 'quote_payment', 'quote_shipping_rate'] as $table) {
            $this->assertNotContains($table, $this->deletedTables());
        }
    }

    public function testShippingRatesSkippedWhenQuoteHasNoAddresses(): void
    {
        $this->deleter($this->order(['quote_id' => 99]))->deleteOrder(self::ORDER_ID);

        $this->assertNotContains('quote_shipping_rate', $this->deletedTables());
        $this->assertContains('quote', $this->deletedTables());
    }

    public function testDeletionLogCapturesOrderSnapshot(): void
    {
        $user = $this->createStub(User::class);
        $user->method('getUserName')->willReturn('admin');
        $this->adminUser = $user;
        $order = $this->order([
            'items' => [
                $this->item('Shirt', 'SH-1', '2.0000', '9.75'),
                $this->item('Cap', 'CP-1', '1', null),
            ],
        ]);

        $this->deleter($order)->deleteOrder(self::ORDER_ID, 'mass');

        $this->assertCount(1, $this->inserts);
        [$table, $log] = $this->inserts[0];
        $this->assertSame('panth_order_deletion_log', $table);
        $this->assertSame('000000042', $log['order_increment_id']);
        $this->assertSame(self::ORDER_ID, $log['order_entity_id']);
        $this->assertSame('Jane Doe', $log['customer_name']);
        $this->assertSame('jane@example.com', $log['customer_email']);
        $this->assertSame(19.5, $log['grand_total']);
        $this->assertSame('EUR', $log['order_currency']);
        $this->assertSame('pending', $log['order_status']);
        $this->assertSame(2, $log['items_count']);
        $this->assertSame(
            [
                ['name' => 'Shirt', 'sku' => 'SH-1', 'qty' => 2, 'price' => 9.75],
                ['name' => 'Cap', 'sku' => 'CP-1', 'qty' => 1, 'price' => 0],
            ],
            json_decode($log['items_summary'], true)
        );
        $this->assertSame('admin', $log['deleted_by']);
        $this->assertSame('203.0.113.7', $log['ip_address']);
        $this->assertSame('mass', $log['deletion_method']);
        $this->assertSame(['Panth_OrderCleanup: Order #000000042 deleted by admin'], $this->logs['info']);
    }

    public function testCustomerNameIsTrimmedAndCappedAt255Characters(): void
    {
        $order = $this->order(['customer_firstname' => '', 'customer_lastname' => str_repeat('a', 300)]);

        $this->deleter($order)->deleteOrder(self::ORDER_ID);

        $name = $this->inserts[0][1]['customer_name'];
        $this->assertSame(255, mb_strlen($name));
        $this->assertSame('a', $name[0]);
    }

    public function testDeletedByFallsBackToUnknownWithoutAdminUser(): void
    {
        $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertSame('unknown', $this->inserts[0][1]['deleted_by']);
        $this->assertSame('single', $this->inserts[0][1]['deletion_method']);
    }

    public function testNoLogRowWhenDeletionLogIsDisabled(): void
    {
        $this->config['panth_order_cleanup/safety/keep_deletion_log'] = false;

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertTrue($result['success']);
        $this->assertSame([], $this->inserts);
    }

    public function testFailureRollsBackAndReportsGenericMessage(): void
    {
        $this->deleteFailure = new \RuntimeException('lock wait timeout');

        $result = $this->deleter()->deleteOrder(self::ORDER_ID);

        $this->assertFalse($result['success']);
        $this->assertStringContainsString('rolled back', $result['message']);
        $this->assertStringNotContainsString('lock wait', $result['message']);
        $this->assertSame(['beginTransaction', 'rollBack'], $this->transaction);
        $this->assertSame([], $this->inserts);
        $this->assertSame(['sales_order_delete_before'], $this->events);
        $this->assertCount(1, $this->logs['error']);
        $this->assertStringContainsString('#000000042: lock wait timeout', $this->logs['error'][0]);
    }
}
