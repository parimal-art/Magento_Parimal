<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Api\Data;

interface SubscriptionInterface
{
    public const SUBSCRIPTION_ID = 'subscription_id';
    public const CUSTOMER_ID = 'customer_id';
    public const PRODUCT_ID = 'product_id';
    public const PARENT_PRODUCT_ID = 'parent_product_id';
    public const QUANTITY = 'quantity';
    public const AGREED_PRICE = 'agreed_price';
    public const FREQUENCY = 'frequency';
    public const DELIVERY_ADDRESS = 'delivery_address';
    public const STATUS = 'status';
    public const NEXT_DUE_DATE = 'next_due_date';
    public const LAST_RUN_DATE = 'last_run_date';
    public const CONSECUTIVE_FAILURE_COUNT = 'consecutive_failure_count';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    public const STATUS_ACTIVE = 'active';
    public const STATUS_PAUSED = 'paused';
    public const STATUS_CANCELLED = 'cancelled';

    // Frequency constants removed — frequencies are now admin-configurable.
    // See Codilar\CoffeeClub\Model\Config\FrequencyProvider.

    /**
     * @return int|null
     */
    public function getSubscriptionId(): ?int;

    /**
     * @param int $subscriptionId
     * @return $this
     */
    public function setSubscriptionId(int $subscriptionId): self;

    /**
     * @return int
     */
    public function getCustomerId(): int;

    /**
     * @param int $customerId
     * @return $this
     */
    public function setCustomerId(int $customerId): self;

    /**
     * @return int
     */
    public function getProductId(): int;

    /**
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * @return int|null
     */
    public function getParentProductId(): ?int;

    /**
     * @param int|null $parentProductId
     * @return $this
     */
    public function setParentProductId(?int $parentProductId): self;

    /**
     * @return int
     */
    public function getQuantity(): int;

    /**
     * @param int $quantity
     * @return $this
     */
    public function setQuantity(int $quantity): self;

    /**
     * @return float
     */
    public function getAgreedPrice(): float;

    /**
     * @param float $agreedPrice
     * @return $this
     */
    public function setAgreedPrice(float $agreedPrice): self;

    /**
     * @return string
     */
    public function getFrequency(): string;

    /**
     * @param string $frequency
     * @return $this
     */
    public function setFrequency(string $frequency): self;

    /**
     * @return string
     */
    public function getDeliveryAddress(): string;

    /**
     * @param string $deliveryAddress
     * @return $this
     */
    public function setDeliveryAddress(string $deliveryAddress): self;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): self;

    /**
     * @return string|null
     */
    public function getNextDueDate(): ?string;

    /**
     * @param string|null $nextDueDate
     * @return $this
     */
    public function setNextDueDate(?string $nextDueDate): self;

    /**
     * @return string|null
     */
    public function getLastRunDate(): ?string;

    /**
     * @param string|null $lastRunDate
     * @return $this
     */
    public function setLastRunDate(?string $lastRunDate): self;

    /**
     * @return int
     */
    public function getConsecutiveFailureCount(): int;

    /**
     * @param int $consecutiveFailureCount
     * @return $this
     */
    public function setConsecutiveFailureCount(int $consecutiveFailureCount): self;
}
