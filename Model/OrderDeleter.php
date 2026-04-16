<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Model;

use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Sales\Api\OrderRepositoryInterface;
use Psr\Log\LoggerInterface;

class OrderDeleter
{
    private const CFG = 'panth_order_cleanup/';

    public function __construct(
        private readonly OrderRepositoryInterface $orderRepository,
        private readonly ResourceConnection $resource,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AdminSession $adminSession,
        private readonly RemoteAddress $remoteAddress,
        private readonly LoggerInterface $logger
    ) {
    }

    /**
     * Delete a single order and its related data.
     *
     * @param int $orderId
     * @param string $method 'single' or 'mass'
     * @return array{success: bool, message: string, increment_id?: string}
     */
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

        // Check allowed statuses
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

        // Log before deletion (capture order data)
        $logData = $this->captureOrderData($order, $method);

        $conn = $this->resource->getConnection();
        $conn->beginTransaction();

        try {
            // Delete related data based on config
            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_invoices')) {
                $this->deleteRelated($conn, 'sales_invoice', 'sales_invoice_grid', 'sales_invoice_item', 'sales_invoice_comment', $orderId);
                $logData['invoices_deleted'] = true;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_shipments')) {
                $this->deleteRelated($conn, 'sales_shipment', 'sales_shipment_grid', 'sales_shipment_item', 'sales_shipment_comment', $orderId);
                $logData['shipments_deleted'] = true;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_creditmemos')) {
                $this->deleteRelated($conn, 'sales_creditmemo', 'sales_creditmemo_grid', 'sales_creditmemo_item', 'sales_creditmemo_comment', $orderId);
                $logData['creditmemos_deleted'] = true;
            }

            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/delete_transactions')) {
                $conn->delete($this->resource->getTableName('sales_payment_transaction'), ['order_id = ?' => $orderId]);
            }

            // Delete order items
            $conn->delete($this->resource->getTableName('sales_order_item'), ['order_id = ?' => $orderId]);

            // Delete order payment
            $conn->delete($this->resource->getTableName('sales_order_payment'), ['parent_id = ?' => $orderId]);

            // Delete order status history
            $conn->delete($this->resource->getTableName('sales_order_status_history'), ['parent_id = ?' => $orderId]);

            // Delete order addresses
            $conn->delete($this->resource->getTableName('sales_order_address'), ['parent_id = ?' => $orderId]);

            // Delete order tax
            $conn->delete($this->resource->getTableName('sales_order_tax'), ['order_id = ?' => $orderId]);

            // Delete from order grid
            $conn->delete($this->resource->getTableName('sales_order_grid'), ['entity_id = ?' => $orderId]);

            // Delete the order itself
            $conn->delete($this->resource->getTableName('sales_order'), ['entity_id = ?' => $orderId]);

            // Delete related quote if exists
            $quoteId = $order->getQuoteId();
            if ($quoteId) {
                // Delete shipping rates via quote_address (quote_shipping_rate uses address_id, not quote_id)
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

            $conn->commit();

            // Save deletion log
            if ($this->scopeConfig->isSetFlag(self::CFG . 'safety/keep_deletion_log')) {
                $this->saveDeletionLog($logData);
            }

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

    private function deleteRelated($conn, string $mainTable, string $gridTable, string $itemTable, string $commentTable, int $orderId): void
    {
        // Get entity IDs from main table
        $entityIds = $conn->fetchCol(
            $conn->select()->from($this->resource->getTableName($mainTable), ['entity_id'])->where('order_id = ?', $orderId)
        );

        if (!empty($entityIds)) {
            $conn->delete($this->resource->getTableName($itemTable), ['parent_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($commentTable), ['parent_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($gridTable), ['entity_id IN (?)' => $entityIds]);
            $conn->delete($this->resource->getTableName($mainTable), ['order_id = ?' => $orderId]);
        }
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
            'customer_name' => trim($order->getCustomerFirstname() . ' ' . $order->getCustomerLastname()),
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

    private function saveDeletionLog(array $data): void
    {
        try {
            $conn = $this->resource->getConnection();
            $conn->insert($this->resource->getTableName('panth_order_deletion_log'), $data);
        } catch (\Exception $e) {
            $this->logger->error('Panth_OrderCleanup: Failed to save deletion log: ' . $e->getMessage());
        }
    }
}
