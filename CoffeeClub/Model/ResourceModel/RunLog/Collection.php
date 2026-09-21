<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\ResourceModel\RunLog;

use Codilar\CoffeeClub\Model\ResourceModel\RunLog as RunLogResource;
use Codilar\CoffeeClub\Model\RunLog;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    /**
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(RunLog::class, RunLogResource::class);
    }
}
