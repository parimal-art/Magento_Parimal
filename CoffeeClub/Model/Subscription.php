<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Magento\Framework\Model\AbstractModel;

class Subscription extends AbstractModel implements SubscriptionInterface
{
    protected function _construct(): void
    {
        $this->_init(\Codilar\CoffeeClub\Model\ResourceModel\Subscription::class);
    }

    public function getSubscriptionId(): ?int
    {
        return $this->getData(self::SUBSCRIPTION_ID) !== null
            ? (int) $this->getData(self::SUBSCRIPTION_ID)
            : null;
    }

    public function setSubscriptionId(int $subscriptionId): SubscriptionInterface
    {
        return $this->setData(self::SUBSCRIPTION_ID, $subscriptionId);
    }

    public function getCustomerId(): int
    {
        return (int) $this->getData(self::CUSTOMER_ID);
    }

    public function setCustomerId(int $customerId): SubscriptionInterface
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    public function getProductId(): int
    {
        return (int) $this->getData(self::PRODUCT_ID);
    }

    public function setProductId(int $productId): SubscriptionInterface
    {
        return $this->setData(self::PRODUCT_ID, $productId);
    }

    public function getParentProductId(): ?int
    {
        $value = $this->getData(self::PARENT_PRODUCT_ID);
        return $value !== null ? (int) $value : null;
    }

    public function setParentProductId(?int $parentProductId): SubscriptionInterface
    {
        return $this->setData(self::PARENT_PRODUCT_ID, $parentProductId);
    }

    public function getQuantity(): int
    {
        return (int) $this->getData(self::QUANTITY);
    }

    public function setQuantity(int $quantity): SubscriptionInterface
    {
        return $this->setData(self::QUANTITY, $quantity);
    }

    public function getAgreedPrice(): float
    {
        return (float) $this->getData(self::AGREED_PRICE);
    }

    public function setAgreedPrice(float $agreedPrice): SubscriptionInterface
    {
        return $this->setData(self::AGREED_PRICE, $agreedPrice);
    }

    public function getFrequency(): string
    {
        return (string) $this->getData(self::FREQUENCY);
    }

    public function setFrequency(string $frequency): SubscriptionInterface
    {
        return $this->setData(self::FREQUENCY, $frequency);
    }

    public function getDeliveryAddress(): string
    {
        return (string) $this->getData(self::DELIVERY_ADDRESS);
    }

    public function setDeliveryAddress(string $deliveryAddress): SubscriptionInterface
    {
        return $this->setData(self::DELIVERY_ADDRESS, $deliveryAddress);
    }

    public function getStatus(): string
    {
        return (string) $this->getData(self::STATUS);
    }

    public function setStatus(string $status): SubscriptionInterface
    {
        return $this->setData(self::STATUS, $status);
    }

    public function getNextDueDate(): ?string
    {
        return $this->getData(self::NEXT_DUE_DATE);
    }

    public function setNextDueDate(?string $nextDueDate): SubscriptionInterface
    {
        return $this->setData(self::NEXT_DUE_DATE, $nextDueDate);
    }

    public function getLastRunDate(): ?string
    {
        return $this->getData(self::LAST_RUN_DATE);
    }

    public function setLastRunDate(?string $lastRunDate): SubscriptionInterface
    {
        return $this->setData(self::LAST_RUN_DATE, $lastRunDate);
    }

    public function getConsecutiveFailureCount(): int
    {
        return (int) $this->getData(self::CONSECUTIVE_FAILURE_COUNT);
    }

    public function setConsecutiveFailureCount(int $consecutiveFailureCount): SubscriptionInterface
    {
        return $this->setData(self::CONSECUTIVE_FAILURE_COUNT, $consecutiveFailureCount);
    }
}
