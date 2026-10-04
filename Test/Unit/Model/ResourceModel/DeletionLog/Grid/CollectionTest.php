<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Model\ResourceModel\DeletionLog\Grid;

use Magento\Framework\DataObject;
use Magento\Framework\Event\ManagerInterface;
use Panth\OrderCleanup\Model\ResourceModel\DeletionLog\Grid\Collection;
use PHPUnit\Framework\TestCase;

class CollectionTest extends TestCase
{
    private function loadItems(array $rows): array
    {
        $reflection = new \ReflectionClass(Collection::class);
        $collection = $reflection->newInstanceWithoutConstructor();

        $items = [];
        foreach ($rows as $row) {
            $items[] = new DataObject($row);
        }

        $property = new \ReflectionProperty(\Magento\Framework\Data\Collection::class, '_items');
        $property->setValue($collection, $items);
        $events = new \ReflectionProperty(
            \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection::class,
            '_eventManager'
        );
        $events->setValue($collection, $this->createStub(ManagerInterface::class));

        $method = new \ReflectionMethod(Collection::class, '_afterLoad');
        $this->assertSame($collection, $method->invoke($collection));

        return $items;
    }

    public function testLogIdBecomesItemId(): void
    {
        [$item] = $this->loadItems([['log_id' => '7']]);

        $this->assertSame('7', $item->getId());
    }

    public function testGrandTotalIsRenderedInTheLoggedOrderCurrency(): void
    {
        [$item] = $this->loadItems([['log_id' => '1', 'order_currency' => 'EUR']]);

        $this->assertSame('EUR', $item->getData('base_currency_code'));
    }

    public function testExistingCurrencyCodeIsKept(): void
    {
        [$item] = $this->loadItems([['log_id' => '1', 'order_currency' => 'EUR', 'base_currency_code' => 'USD']]);

        $this->assertSame('USD', $item->getData('base_currency_code'));
    }

    public function testMissingCurrencyLeavesStoreDefault(): void
    {
        [$item] = $this->loadItems([['log_id' => '1', 'order_currency' => null]]);

        $this->assertNull($item->getData('base_currency_code'));
    }

    public function testSearchResultContract(): void
    {
        $reflection = new \ReflectionClass(Collection::class);
        $collection = $reflection->newInstanceWithoutConstructor();

        $this->assertNull($collection->getSearchCriteria());
        $this->assertSame($collection, $collection->setAggregations(['a' => 1]));
        $this->assertSame(['a' => 1], $collection->getAggregations());
        $this->assertSame($collection, $collection->setTotalCount(5));
        $this->assertSame($collection, $collection->setItems(null));
    }
}
