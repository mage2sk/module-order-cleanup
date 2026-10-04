<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\Log;

use Magento\Backend\Model\View\Result\Page;
use Magento\Framework\View\Page\Config;
use Magento\Framework\View\Page\Title;
use Magento\Framework\View\Result\PageFactory;
use Panth\OrderCleanup\Controller\Adminhtml\Log\Index;
use Panth\OrderCleanup\Test\Unit\Controller\Adminhtml\ControllerTestCase;

class IndexTest extends ControllerTestCase
{
    public function testRendersDeletionLogPageWithMenuAndTitle(): void
    {
        $title = $this->createMock(Title::class);
        $title->expects($this->once())->method('prepend')->with(
            $this->callback(static fn($value) => (string)$value === 'Order Deletion Log')
        );
        $config = $this->createStub(Config::class);
        $config->method('getTitle')->willReturn($title);

        $page = $this->createMock(Page::class);
        $page->expects($this->once())->method('setActiveMenu')->with('Panth_OrderCleanup::deletion_log');
        $page->method('getConfig')->willReturn($config);

        $factory = $this->createStub(PageFactory::class);
        $factory->method('create')->willReturn($page);

        $this->assertSame($page, (new Index($this->context(), $factory))->execute());
    }

    public function testRequiresViewLogPermission(): void
    {
        $this->assertSame('Panth_OrderCleanup::view_log', Index::ADMIN_RESOURCE);
    }
}
