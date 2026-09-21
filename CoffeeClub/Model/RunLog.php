<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Codilar\CoffeeClub\Api\Data\RunLogInterface;
use Magento\Framework\Model\AbstractModel;

class RunLog extends AbstractModel implements RunLogInterface
{
    protected function _construct(): void
    {
        $this->_init(\Codilar\CoffeeClub\Model\ResourceModel\RunLog::class);
    }

    public function getLogId(): ?int
    {
        return $this->getData(self::LOG_ID) !== null
            ? (int) $this->getData(self::LOG_ID)
            : null;
    }

    public function setLogId(int $logId): RunLogInterface
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    public function getSubscriptionId(): int
    {
        return (int) $this->getData(self::SUBSCRIPTION_ID);
    }

    public function setSubscriptionId(int $subscriptionId): RunLogInterface
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    public function getRunDate(): string
    {
        return (string) $this->getData(self::RUN_DATE);
    }

    public function setRunDate(string $runDate): RunLogInterface
    {
        return $this->setData(self::RUN_DATE, $runDate);
    }

    public function getOutcome(): string
    {
        return (string) $this->getData(self::OUTCOME);
    }

    public function setOutcome(string $outcome): RunLogInterface
    {
        return $this->setData(self::OUTCOME, $outcome);
    }

    public function getOrderId(): ?int
    {
        return $this->getData(self::ORDER_ID) !== null
            ? (int) $this->getData(self::ORDER_ID)
            : null;
    }

    public function setOrderId(?int $orderId): RunLogInterface
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    public function getFailureReason(): ?string
    {
        return $this->getData(self::FAILURE_REASON);
    }

    public function setFailureReason(?string $failureReason): RunLogInterface
    {
        return $this->setData(self::FAILURE_REASON, $failureReason);
    }
}
