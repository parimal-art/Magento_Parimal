<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class RunLog extends AbstractDb
{
    protected function _construct(): void
    {
        $this->_init('codilar_coffeeclub_run_log', 'log_id');
    }
}
