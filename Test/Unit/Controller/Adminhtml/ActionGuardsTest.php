<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Controller\Adminhtml;

use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\CsrfAwareActionInterface;
use Panth\OrderCleanup\Controller\Adminhtml\Log\Index;
use Panth\OrderCleanup\Controller\Adminhtml\Order\Delete;
use Panth\OrderCleanup\Controller\Adminhtml\Order\MassDelete;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ActionGuardsTest extends TestCase
{
    public static function destructiveActions(): array
    {
        return [
            'single delete' => [Delete::class, 'Panth_OrderCleanup::delete_order'],
            'mass delete' => [MassDelete::class, 'Panth_OrderCleanup::mass_delete'],
        ];
    }

    #[DataProvider('destructiveActions')]
    public function testDestructiveActionsArePostOnly(string $class, string $resource): void
    {
        $this->assertTrue(is_subclass_of($class, HttpPostActionInterface::class));
        $this->assertFalse(is_subclass_of($class, HttpGetActionInterface::class));
        $this->assertStringStartsWith('Panth_OrderCleanup::', $resource);
    }

    #[DataProvider('destructiveActions')]
    public function testDestructiveActionsKeepBackendFormKeyValidation(string $class, string $resource): void
    {
        $this->assertTrue(is_subclass_of($class, Action::class));
        $this->assertFalse(is_subclass_of($class, CsrfAwareActionInterface::class));
        $this->assertSame($resource, $class::ADMIN_RESOURCE);
        $reflection = new \ReflectionClass($class);
        foreach (['_processUrlKeys', '_isAllowed', 'dispatch', '_validateSecretKey'] as $method) {
            $this->assertNotSame(
                $class,
                $reflection->getMethod($method)->getDeclaringClass()->getName(),
                $method . ' must not be overridden'
            );
        }
    }

    #[DataProvider('destructiveActions')]
    public function testDestructiveActionsRequireTheirOwnAclResource(string $class, string $resource): void
    {
        $this->assertSame($resource, $class::ADMIN_RESOURCE);
        $this->assertContains($resource, $this->aclResources());
    }

    public function testLogAndConfigResourcesAreDeclared(): void
    {
        $this->assertContains(Index::ADMIN_RESOURCE, $this->aclResources());
        $this->assertContains('Panth_OrderCleanup::config', $this->aclResources());
    }

    public function testDeleteResourcesSitUnderSales(): void
    {
        $xml = $this->loadXml('etc/acl.xml');
        $ids = array_map('strval', $xml->xpath('//resource[@id="Magento_Sales::sales"]/resource/@id') ?: []);

        $this->assertSame(
            ['Panth_OrderCleanup::delete_order', 'Panth_OrderCleanup::mass_delete', 'Panth_OrderCleanup::view_log'],
            $ids
        );
    }

    public function testMenuEntriesUseDeclaredResources(): void
    {
        $resources = array_map('strval', $this->loadXml('etc/adminhtml/menu.xml')->xpath('//add/@resource') ?: []);

        $this->assertNotEmpty($resources);
        foreach ($resources as $resource) {
            $this->assertContains($resource, $this->aclResources());
        }
    }

    public function testDefaultsKeepEverySafetyGuardOn(): void
    {
        $xml = $this->loadXml('etc/config.xml');
        $safety = $xml->default->panth_order_cleanup->safety;
        $mass = $xml->default->panth_order_cleanup->mass_action;

        $this->assertSame('1', (string)$safety->require_confirmation);
        $this->assertSame('1', (string)$safety->require_type_order_id);
        $this->assertSame('1', (string)$safety->keep_deletion_log);
        $this->assertSame('canceled,closed,holded,pending,fraud', (string)$safety->allowed_statuses);
        $this->assertSame('1', (string)$mass->require_confirmation);
        $this->assertSame('50', (string)$mass->max_orders_per_action);
    }

    private function aclResources(): array
    {
        return array_map('strval', $this->loadXml('etc/acl.xml')->xpath('//resource/@id') ?: []);
    }

    private function loadXml(string $file): \SimpleXMLElement
    {
        $path = dirname(__DIR__, 4) . '/' . $file;
        $this->assertTrue(is_file($path), $file . ' is missing');
        $previous = libxml_use_internal_errors(true);
        $xml = simplexml_load_string((string)file_get_contents($path));
        libxml_use_internal_errors($previous);
        $this->assertInstanceOf(\SimpleXMLElement::class, $xml);
        return $xml;
    }
}
