<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Widget\Context;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\ObjectManagerInterface;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Panth\OrderCleanup\Block\Adminhtml\Order\View\DeleteButton;
use PHPUnit\Framework\TestCase;

class DeleteButtonTest extends TestCase
{
    private const SHOWN = [
        'panth_order_cleanup/general/enabled' => true,
        'panth_order_cleanup/button/show_on_order_view' => true,
    ];

    private ?ObjectManagerInterface $previousObjectManager = null;

    protected function setUp(): void
    {
        $property = new \ReflectionProperty(ObjectManager::class, '_instance');
        $this->previousObjectManager = $property->getValue();
        ObjectManager::setInstance($this->createStub(ObjectManagerInterface::class));
    }

    protected function tearDown(): void
    {
        (new \ReflectionProperty(ObjectManager::class, '_instance'))->setValue(null, $this->previousObjectManager);
    }

    private function button(array $config, ?Order $order, bool $allowed = true, ?UrlInterface $url = null): DeleteButton
    {
        $context = $this->createStub(Context::class);
        $context->method('getUrlBuilder')->willReturn($url ?? $this->createStub(UrlInterface::class));

        $scopeConfig = $this->createStub(ScopeConfigInterface::class);
        $scopeConfig->method('isSetFlag')->willReturnCallback(static fn($path) => (bool)($config[$path] ?? false));
        $scopeConfig->method('getValue')->willReturnCallback(static fn($path) => $config[$path] ?? null);

        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturnCallback(
            static fn($resource) => $allowed && $resource === 'Panth_OrderCleanup::delete_order'
        );

        $registry = $this->createStub(Registry::class);
        $registry->method('registry')->willReturnCallback(
            static fn($key) => $key === 'current_order' ? $order : null
        );

        return new DeleteButton($context, $scopeConfig, $authorization, $registry);
    }

    private function order(): Order
    {
        $order = $this->createStub(Order::class);
        $order->method('getEntityId')->willReturn('55');
        $order->method('getIncrementId')->willReturn(100000055);
        return $order;
    }

    public function testShownWhenOrderPresentEnabledAndAllowed(): void
    {
        $this->assertTrue($this->button(self::SHOWN, $this->order())->canShow());
    }

    public function testHiddenWithoutCurrentOrder(): void
    {
        $this->assertFalse($this->button(self::SHOWN, null)->canShow());
    }

    public function testHiddenWhenModuleDisabled(): void
    {
        $config = ['panth_order_cleanup/general/enabled' => false] + self::SHOWN;

        $this->assertFalse($this->button($config, $this->order())->canShow());
    }

    public function testHiddenWhenOrderViewButtonDisabled(): void
    {
        $config = ['panth_order_cleanup/button/show_on_order_view' => false] + self::SHOWN;

        $this->assertFalse($this->button($config, $this->order())->canShow());
    }

    public function testHiddenWithoutAclPermission(): void
    {
        $this->assertFalse($this->button(self::SHOWN, $this->order(), false)->canShow());
    }

    public function testDeleteUrlTargetsCurrentOrder(): void
    {
        $url = $this->createStub(UrlInterface::class);
        $url->method('getUrl')->willReturnCallback(
            static fn($route, $params) => $route . '?' . http_build_query($params)
        );

        $button = $this->button(self::SHOWN, $this->order(), true, $url);

        $this->assertSame('panth_ordercleanup/order/delete?order_id=55', $button->getDeleteUrl());
        $this->assertSame('100000055', $button->getOrderIncrementId());
    }

    public function testButtonTextFallsBackToDefault(): void
    {
        $this->assertSame('Delete This Order', $this->button([], null)->getButtonText());
        $this->assertSame(
            'Remove test order',
            $this->button(['panth_order_cleanup/button/button_text' => 'Remove test order'], null)->getButtonText()
        );
    }

    public function testButtonColorAcceptsOnlyHexColors(): void
    {
        $key = 'panth_order_cleanup/button/button_color';

        $this->assertSame('#abc', $this->button([$key => ' #abc '], null)->getButtonColor());
        $this->assertSame('#00FF7a', $this->button([$key => '#00FF7a'], null)->getButtonColor());
        $this->assertSame('#DC2626', $this->button([$key => 'red'], null)->getButtonColor());
        $this->assertSame('#DC2626', $this->button([$key => '#abcd'], null)->getButtonColor());
        $this->assertSame('#DC2626', $this->button([$key => '#fff;background:url(x)'], null)->getButtonColor());
        $this->assertSame('#DC2626', $this->button([], null)->getButtonColor());
    }

    public function testConfirmationFlags(): void
    {
        $button = $this->button([
            'panth_order_cleanup/safety/require_confirmation' => true,
            'panth_order_cleanup/safety/require_type_order_id' => false,
        ], null);

        $this->assertTrue($button->requireConfirmation());
        $this->assertFalse($button->requireTypeOrderId());
        $this->assertTrue(
            $this->button(['panth_order_cleanup/safety/require_type_order_id' => '1'], null)->requireTypeOrderId()
        );
    }

    public function testRelatedDocumentsTextFollowsSafetySettings(): void
    {
        $invoices = 'panth_order_cleanup/safety/delete_invoices';
        $shipments = 'panth_order_cleanup/safety/delete_shipments';
        $memos = 'panth_order_cleanup/safety/delete_creditmemos';

        $this->assertSame(
            'Related invoices, shipments, and credit memos',
            $this->button([$invoices => true, $shipments => true, $memos => true], null)->getRelatedDocumentsText()
        );
        $this->assertSame(
            'Related invoices and credit memos',
            $this->button([$invoices => true, $memos => true], null)->getRelatedDocumentsText()
        );
        $this->assertSame('Related shipments', $this->button([$shipments => true], null)->getRelatedDocumentsText());
        $this->assertSame('', $this->button([], null)->getRelatedDocumentsText());
    }

    public function testDeletesTransactionsFollowsSetting(): void
    {
        $key = 'panth_order_cleanup/safety/delete_transactions';

        $this->assertTrue($this->button([$key => true], null)->deletesTransactions());
        $this->assertFalse($this->button([$key => false], null)->deletesTransactions());
    }
}
