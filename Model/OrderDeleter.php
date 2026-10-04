<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Model;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Sql\Expression;
use Magento\Framework\Event\ManagerInterface as EventManager;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class OrderDeleter
{
    private const CFG = 'panth_order_cleanup/';

    private const RELATED_DOCUMENTS = [
        'delete_invoices' => ['sales_invoice', 'invoices'],
        'delete_shipments' => ['sales_shipment', 'shipments'],
        'delete_creditmemos' => ['sales_creditmemo', 'credit memos'],
        'delete_transactions' => ['sales_payment_transaction', 'payment transactions'],
    ];

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ResourceConnection $resource,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AdminSession $adminSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger,
        private readonly EventManager $eventManager
    ) {
    }

    public function deleteOrder(int $orderId, string $method = 'single'): array
    {
        if (!$this->scopeConfig->isSetFlag(self::CFG . 'general/enabled')) {
            return ['success' => false, 'message' => 'Order Cleanup is currently disabled. Please enable it under Stores > Configuration > Panth Extensions > Order Cleanup.'];
        }

        try {
            $order = $this->orderRepository->get($orderId);
        } catch (\Exception $e) {
            return ['success' => false, 'message' => 'The requested order could not be found. It may have already been deleted.'];
        }

        $incrementId = $order->getIncrementId();
        $status = $order->getStatus();

        $allowedStatuses = $this->scopeConfig->getValue(self::CFG . 'safety/allowed_statuses');
        if (!empty($allowedStatuses)) {
            $allowed = array_map('trim', explode(',', $allowedStatuses));
            if (!in_array($status, $allowed, true)) {
                return [
                    'success' => false,
                    'message' => "Order #{$incrementId} cannot be deleted because its current status is \"{$status}\". Only orders with the following statuses can be deleted: " . implode(', ', $allowed) . '.',
                ];
            }
        }

        $conn = $this->resource->getConnection();

        foreach (self::RELATED_DOCUMENTS as $setting => [$table, $label]) {
            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/' . $setting)) {
                continue;
            }
            $count = (int) $conn->fetchOne(
                $conn->select()
                    ->from($this->resource->getTableName($table), ['cnt' => new Expression('COUNT(*)')])
                    ->where('order_id = ?', $orderId)
            );
            if ($count > 0) {
                return [
                    'success' => false,
                    'message' => "Order #{$incrementId} cannot be deleted because it has {$label} and deleting {$label} is disabled in the Order Cleanup configuration.",
                ];
            }
        }

        $logData = $this->captureOrderData($order, $method);

        $conn->beginTransaction();

        try {
            $eventData = ['data_object' => $order, 'order' => $order];
            $this->eventManager->dispatch('sales_order_delete_before', $eventData);

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_invoices')) {
                $logData['invoices_deleted'] = $this->deleteRelated($conn, 'sales_invoice', 'sales_invoice_grid', 'sales_invoice_item', 'sales_invoice_comment', $orderId) > 0;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_shipments')) {
                $logData['shipments_deleted'] = $this->deleteRelated($conn, 'sales_shipment', 'sales_shipment_grid', 'sales_shipment_item', 'sales_shipment_comment', $orderId) > 0;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_creditmemos')) {
                $logData['creditmemos_deleted'] = $this->deleteRelated($conn, 'sales_creditmemo', 'sales_creditmemo_grid', 'sales_creditmemo_item', 'sales_creditmemo_comment', $orderId) > 0;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_transactions')) {
                $conn->delete($this->resource->getTableName('sales_payment_transaction'), ['order_id = ?' => $orderId]);
            }

            $taxIds = $conn->fetchCol(
                $conn->select()
                    ->from($this->resource->getTableName('sales_order_tax'), ['tax_id'])
                    ->where('order_id = ?', $orderId)
            );
            if (!empty($taxIds)) {
                $conn->delete($this->resource->getTableName('sales_order_tax_item'), ['tax_id IN (?)' => $taxIds]);
            }

            $conn->delete($this->resource->getTableName('sales_order_item'), ['order_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order_payment'), ['parent_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order_status_history'), ['parent_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order_address'), ['parent_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order_tax'), ['order_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order_grid'), ['entity_id = ?' => $orderId]);

            $conn->delete($this->resource->getTableName('sales_order'), ['entity_id = ?' => $orderId]);

            $quoteId = $order->getQuoteId();
            if ($quoteId) {
                $addressIds = $conn->fetchCol(
                    $conn->select()
                        ->from($this->resource->getTableName('quote_address'), ['address_id'])
                        ->where('quote_id = ?', $quoteId)
                );
                if (!empty($addressIds)) {
                    $conn->delete($this->resource->getTableName('quote_shipping_rate'), ['address_id IN (?)' => $addressIds]);
                }

                $conn->delete($this->resource->getTableName('quote_item'), ['quote_id = ?' => $quoteId]);
                $conn->delete($this->resource->getTableName('quote_address'), ['quote_id = ?' => $quoteId]);
                $conn->delete($this->resource->getTableName('quote_payment'), ['quote_id = ?' => $quoteId]);
                $conn->delete($this->resource->getTableName('quote'), ['entity_id = ?' => $quoteId]);
            }

            $this->eventManager->dispatch('sales_order_delete_after', $eventData);

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/keep_deletion_log')) {
                $conn->insert($this->resource->getTableName('panth_order_deletion_log'), $logData);
            }

            $conn->commit();

            $this->logger->info("Panth_OrderCleanup: Order #{$incrementId} deleted by " . $logData['deleted_by']);

            return [
                'success' => true,
                'message' => "Order #{$incrementId} and all related data have been permanently deleted.",
                'increment_id' => $incrementId,
            ];
        } catch (\Exception $e) {
            $conn->rollBack();
            $this->logger->error('Panth_OrderCleanup: Failed to delete order #' . $incrementId . ': ' . $e->getMessage());
            return ['success' => false, 'message' => "Something went wrong while deleting order #{$incrementId}. The operation has been rolled back and no data was lost. Please check the system log for details."];
        }
    }

    private function deleteRelated($conn, string $mainTable, string $gridTable, string $itemTable, string $commentTable, int $orderId): int
    {
        $entityIds = $conn->fetchCol(
            $conn->select()->from($this->resource->getTableName($mainTable), ['entity_id'])->where('order_id = ?', $orderId)
        );

        if (!empty($entityIds)) {
            $conn->delete($this->resource->getTableName($itemTable), ['parent_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($commentTable), ['parent_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($gridTable), ['entity_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($mainTable), ['order_id = ?' => $orderId]);
        }
        return count($entityIds);
    }

    private function captureOrderData($order, string $method): array
    {
        $items = [];
        foreach ($order->getAllVisibleItems() as $item) {
            $items[] = [
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'qty' => (int) $item->getQtyOrdered(),
                'price' => (float) $item->getPrice(),
            ];
        }

        $adminUser = $this->adminSession->getUser();

        return [
            'order_increment_id' => $order->getIncrementId(),
            'order_entity_id' => (int) $order->getEntityId(),
            'customer_name' => mb_substr(trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()), 0, 255),
            'customer_email' => $order->getCustomerEmail(),
            'grand_total' => (float) $order->getGrandTotal(),
            'order_currency' => $order->getOrderCurrencyCode(),
            'order_status' => $order->getStatus(),
            'items_count' => count($items),
            'items_summary' => json_encode($items),
            'invoices_deleted' => false,
            'shipments_deleted' => false,
            'creditmemos_deleted' => false,
            'deleted_by' => $adminUser ? $adminUser->getUserName() : 'unknown',
            'ip_address' => $this->remoteAddress->getRemoteAddress(),
            'deletion_method' => $method,
        ];
    }
}
