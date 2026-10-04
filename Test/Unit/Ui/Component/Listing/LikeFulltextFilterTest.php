<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Ui\Component\Listing;

use Magento\Framework\Api\Filter;
use Magento\Framework\Data\Collection;
use Magento\Framework\Data\Collection\AbstractDb;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Panth\OrderCleanup\Ui\Component\Listing\LikeFulltextFilter;
use PHPUnit\Framework\TestCase;

class LikeFulltextFilterTest extends TestCase
{
    /** @var string[] */
    private array $wheres = [];

    private function collection(): AbstractDb
    {
        $this->wheres = [];
        $connection = $this->createStub(AdapterInterface::class);
        $connection->method('quoteIdentifier')->willReturnCallback(static fn($c) => '`' . $c . '`');
        $connection->method('quoteInto')->willReturnCallback(
            static fn($text, $value) => str_replace('?', "'" . $value . "'", $text)
        );

        $select = $this->createStub(Select::class);
        $select->method('where')->willReturnCallback(function ($cond) use ($select) {
            $this->wheres[] = $cond;
            return $select;
        });

        $collection = $this->createStub(AbstractDb::class);
        $collection->method('getConnection')->willReturn($connection);
        $collection->method('getSelect')->willReturn($select);
        return $collection;
    }

    private function filter($value): Filter
    {
        return new Filter(['value' => $value]);
    }

    public function testBuildsOrConditionAcrossConfiguredColumns(): void
    {
        $collection = $this->collection();

        (new LikeFulltextFilter(['order_increment_id', 'customer_email']))->apply($collection, $this->filter(' jane '));

        $this->assertSame(
            ["`order_increment_id` LIKE '%jane%' OR `customer_email` LIKE '%jane%'"],
            $this->wheres
        );
    }

    public function testEscapesLikeWildcardsAndBackslash(): void
    {
        $collection = $this->collection();

        (new LikeFulltextFilter(['customer_name']))->apply($collection, $this->filter('50%_a\\b'));

        $this->assertSame(["`customer_name` LIKE '%50\\%\\_a\\\\b%'"], $this->wheres);
    }

    public function testValueIsTruncatedTo200Characters(): void
    {
        $collection = $this->collection();

        (new LikeFulltextFilter(['c']))->apply($collection, $this->filter(str_repeat('x', 250)));

        $this->assertSame(["`c` LIKE '%" . str_repeat('x', 200) . "%'"], $this->wheres);
    }

    public function testNonStringColumnsAreIgnored(): void
    {
        $collection = $this->collection();

        (new LikeFulltextFilter(['a' => 'email', 'b' => 5, 'c' => null]))->apply($collection, $this->filter('q'));

        $this->assertSame(["`email` LIKE '%q%'"], $this->wheres);
    }

    public function testBlankOrNonScalarValueAddsNoCondition(): void
    {
        $collection = $this->collection();
        $filter = new LikeFulltextFilter(['email']);

        $filter->apply($collection, $this->filter('   '));
        $filter->apply($collection, $this->filter(['x']));
        $filter->apply($collection, $this->filter(null));

        $this->assertSame([], $this->wheres);
    }

    public function testNoColumnsAddsNoCondition(): void
    {
        $collection = $this->collection();

        (new LikeFulltextFilter([]))->apply($collection, $this->filter('q'));

        $this->assertSame([], $this->wheres);
    }

    public function testNonDbCollectionIsIgnored(): void
    {
        $collection = $this->createStub(Collection::class);

        (new LikeFulltextFilter(['email']))->apply($collection, $this->filter('q'));

        $this->assertSame([], $this->wheres);
    }
}
