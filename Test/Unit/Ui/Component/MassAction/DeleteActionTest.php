<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Ui\Component\MassAction;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\AuthorizationInterface;
use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponent\Processor;
use Panth\OrderCleanup\Ui\Component\MassAction\DeleteAction;
use PHPUnit\Framework\TestCase;

class DeleteActionTest extends TestCase
{
    private const ALL_ON = [
        'panth_order_cleanup/general/enabled' => true,
        'panth_order_cleanup/mass_action/enabled' => true,
        'panth_order_cleanup/mass_action/require_confirmation' => true,
    ];

    private function prepared(array $flags, bool $allowed = true): array
    {
        $context = $this->createStub(ContextInterface::class);
        $context->method('getProcessor')->willReturn($this->createStub(Processor::class));

        $config = $this->createStub(ScopeConfigInterface::class);
        $config->method('isSetFlag')->willReturnCallback(static fn($path) => (bool)($flags[$path] ?? false));

        $authorization = $this->createStub(AuthorizationInterface::class);
        $authorization->method('isAllowed')->willReturnCallback(
            static fn($resource) => $allowed && $resource === 'Panth_OrderCleanup::mass_delete'
        );

        $action = new DeleteAction($context, $config, $authorization, [], [
            'name' => 'panth_delete',
            'config' => [
                'type' => 'panth_delete',
                'label' => 'Delete',
                'confirm' => ['title' => 'Delete orders', 'message' => 'Sure?'],
            ],
        ]);
        $action->prepare();

        return $action->getConfiguration();
    }

    public function testFullyEnabledActionKeepsConfirmAndIsNotDisabled(): void
    {
        $config = $this->prepared(self::ALL_ON);

        $this->assertArrayNotHasKey('actionDisable', $config);
        $this->assertSame(['title' => 'Delete orders', 'message' => 'Sure?'], $config['confirm']);
        $this->assertSame('Delete', $config['label']);
    }

    public function testDisabledModuleDisablesAction(): void
    {
        $config = $this->prepared(['panth_order_cleanup/general/enabled' => false] + self::ALL_ON);

        $this->assertTrue($config['actionDisable']);
    }

    public function testDisabledMassActionDisablesAction(): void
    {
        $config = $this->prepared(['panth_order_cleanup/mass_action/enabled' => false] + self::ALL_ON);

        $this->assertTrue($config['actionDisable']);
    }

    public function testMissingAclPermissionDisablesAction(): void
    {
        $config = $this->prepared(self::ALL_ON, false);

        $this->assertTrue($config['actionDisable']);
    }

    public function testConfirmationRemovedWhenNotRequired(): void
    {
        $config = $this->prepared(['panth_order_cleanup/mass_action/require_confirmation' => false] + self::ALL_ON);

        $this->assertArrayNotHasKey('confirm', $config);
        $this->assertArrayNotHasKey('actionDisable', $config);
    }
}
