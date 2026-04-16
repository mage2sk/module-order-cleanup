<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Ui\Component\MassAction\Filter;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory;
use Panth\OrderCleanup\Model\OrderDeleter;

class MassDelete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_OrderCleanup::mass_delete';

    public function __construct(
        Context $context,
        private readonly Filter $filter,
        private readonly CollectionFactory $collectionFactory,
        private readonly OrderDeleter $orderDeleter,
        private readonly ScopeConfigInterface $scopeConfig
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        if (!$this->scopeConfig->isSetFlag('panth_order_cleanup/mass_action/enabled')) {
            $this->messageManager->addErrorMessage(__('Mass delete is disabled in configuration.'));
            return $this->_redirect('sales/order/index');
        }

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(__('Could not load orders: %1', $e->getMessage()));
            return $this->_redirect('sales/order/index');
        }

        $maxPerAction = (int) ($this->scopeConfig->getValue('panth_order_cleanup/mass_action/max_orders_per_action') ?: 50);
        $totalSelected = $collection->getSize();

        if ($totalSelected > $maxPerAction) {
            $this->messageManager->addErrorMessage(
                __('You selected %1 orders but the maximum per action is %2. Please select fewer orders.', $totalSelected, $maxPerAction)
            );
            return $this->_redirect('sales/order/index');
        }

        $deleted = 0;
        $failed = 0;
        $errors = [];

        foreach ($collection as $order) {
            $result = $this->orderDeleter->deleteOrder((int) $order->getEntityId(), 'mass');
            if ($result['success']) {
                $deleted++;
            } else {
                $failed++;
                $errors[] = $result['message'];
            }
        }

        if ($deleted > 0) {
            $this->messageManager->addSuccessMessage(
                __('%1 order(s) have been permanently deleted.', $deleted)
            );
        }

        if ($failed > 0) {
            $this->messageManager->addErrorMessage(
                __('%1 order(s) could not be deleted. %2', $failed, implode(' | ', array_unique($errors)))
            );
        }

        return $this->_redirect('sales/order/index');
    }
}
