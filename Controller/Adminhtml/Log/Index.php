<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Controller\Adminhtml\Log;

use Magento\Backend\App\Action;
use Magento\Framework\View\Result\PageFactory;

class Index extends Action
{
    public const ADMIN_RESOURCE = 'Panth_OrderCleanup::view_log';

    public function __construct(
        Action\Context $context,
        private readonly PageFactory $pageFactory
    ) {
        parent::__construct($context);
    }

    public function execute()
    {
        $page = $this->pageFactory->create();
        $page->setActiveMenu('Panth_OrderCleanup::deletion_log');
        $page->getConfig()->getTitle()->prepend(__('Order Deletion Log'));
        return $page;
    }
}
