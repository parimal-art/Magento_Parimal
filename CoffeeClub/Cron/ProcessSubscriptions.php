<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Cron;

use Codilar\CoffeeClub\Model\Subscription\Fulfilment;
use Psr\Log\LoggerInterface;

class ProcessSubscriptions
{
    public function __construct(
        protected Fulfilment $fulfilment,
        protected LoggerInterface $logger
    ) {
    }

    /**
     * Entry point for the cron job
     *
     * @return void
     */
    public function execute(): void
    {
        $this->logger->info('CoffeeClub: Starting subscription processing.');
        $processed = $this->fulfilment->processDueSubscriptions();
        $this->logger->info(sprintf('CoffeeClub: Finished. Processed %d subscriptions.', $processed));
    }
}
