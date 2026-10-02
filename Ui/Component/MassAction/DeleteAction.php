<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Ui\Component\MassAction;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Ui\Component\Action;

class DeleteAction extends Action
{
    private const CFG = 'panth_order_cleanup/';

    public function __construct(
        ContextInterface $context,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly AuthorizationInterface $authorization,
        array $components = [],
        array $data = [],
        $actions = null
    ) {
        parent::__construct($context, $components, $data, $actions);
    }

    public function prepare()
    {
        parent::prepare();

        $config = $this->getConfiguration();

        if (!$this->scopeConfig->isSetFlag(self::CFG . 'general/enabled')
            || !$this->scopeConfig->isSetFlag(self::CFG . 'mass_action/enabled')
            || !$this->authorization->isAllowed('Panth_OrderCleanup::mass_delete')
        ) {
            $config['actionDisable'] = true;
        }

        if (!$this->scopeConfig->isSetFlag(self::CFG . 'mass_action/require_confirmation')) {
            unset($config['confirm']);
        }

        $this->setData('config', $config);
    }
}
