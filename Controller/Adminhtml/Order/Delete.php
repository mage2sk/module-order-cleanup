<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Panth\OrderCleanup\Model\OrderDeleter;

class Delete extends Action implements HttpPostActionInterface
{
    public const ADMIN_RESOURCE = 'Panth_OrderCleanup::delete_order';

    public function __construct(
        Context $context,
        private readonly OrderDeleter $orderDeleter
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $orderId = (int) $this->getRequest()->getParam('order_id');

        if (!$orderId) {
            $this->messageManager->addErrorMessage(__('No order ID specified.'));
            return $this->_redirect('sales/order/index');
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
