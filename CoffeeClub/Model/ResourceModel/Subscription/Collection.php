<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\ResourceModel\Subscription;

use Codilar\CoffeeClub\Model\ResourceModel\Subscription as SubscriptionResource;
use Codilar\CoffeeClub\Model\Subscription;
use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{
    protected function _construct(): void
    {
        $this->_init(Subscription::class, SubscriptionResource::class);
    }
}
