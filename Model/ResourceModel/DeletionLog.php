<?php
declare(strict_types=1);

namespace Panth\OrderCleanup\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class DeletionLog extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('panth_order_deletion_log', 'log_id');
    }
}
