<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Panth\OrderCleanup\Model\OrderDeleter;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_OrderCleanup::delete_order';

    public function __construct(
        Context $context,
        private readonly OrderDeleter $orderDeleter,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly OrderRepositoryInterface $orderRepository
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('Unable to process the request. No order was specified.'));
            return $this->_redirect('sales/order/index');
        }

        if ($this->scopeConfig->isSetFlag('panth_order_cleanup/safety/require_type_order_id')) {
            try {
                $expected = (string) $this->orderRepository->get($orderId)->getIncrementId();
            } catch (\Exception $e) {
                $this->messageManager->addErrorMessage(__('The requested order could not be found. It may have already been deleted.'));
                return $this->_redirect('sales/order/index');
            }

            $typed = trim((string) $this->getRequest()->getParam('confirm_increment_id', ''));
            if ($expected === '' || $typed !== $expected) {
                $this->messageManager->addErrorMessage(
                    __('The order was not deleted. Type the order number exactly to confirm the deletion.')
                );
                return $this->_redirect('sales/order/view', ['order_id' => $orderId]);
            }
        }

        $result = $this->orderDeleter->deleteOrder($orderId, 'single');

        if ($result['success']) {
            $this->messageManager->addSuccessMessage(__($result['message']));
        } else {
            $this->messageManager->addErrorMessage(__($result['message']));
        }

        return $this->_redirect('sales/order/index');
    }
}
