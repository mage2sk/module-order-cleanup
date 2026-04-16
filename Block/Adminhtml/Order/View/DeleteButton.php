<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;

class DeleteButton extends Template
{
    private const CFG = 'panth_order_cleanup/';

    protected $_template = 'Panth_OrderCleanup::order/view/delete-button.phtml';

    public function __construct(
        Context $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AuthorizationInterface $authorization,
        private readonly Registry $registry,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    public function canShow(): bool
    {
        return $this->getOrder() !== null
            && $this->scopeConfig->isSetFlag(self::CFG . 'general/enabled')
            && $this->scopeConfig->isSetFlag(self::CFG . 'button/show_on_order_view')
            && $this->authorization->isAllowed('Panth_OrderCleanup::delete_order');
    }

    public function getOrder()
    {
        return $this->registry->registry('current_order');
    }

    public function getDeleteUrl(): string
    {
        return $this->getUrl('panth_ordercleanup/order/delete', [
            'order_id' => $this->getOrder()->getEntityId(),
        ]);
    }

    public function getOrderIncrementId(): string
    {
        return (string) $this->getOrder()->getIncrementId();
    }

    public function getButtonText(): string
    {
        return (string) ($this->scopeConfig->getValue(self::CFG . 'button/button_text') ?: 'Delete This Order');
    }

    public function getButtonColor(): string
    {
        return (string) ($this->scopeConfig->getValue(self::CFG . 'button/button_color') ?: '#DC2626');
    }

    public function requireConfirmation(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CFG . 'safety/require_confirmation');
    }

    public function requireTypeOrderId(): bool
    {
        return $this->scopeConfig->isSetFlag(self::CFG . 'safety/require_type_order_id');
    }
}
