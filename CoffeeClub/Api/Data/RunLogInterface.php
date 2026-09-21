<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Api\Data;

interface RunLogInterface
{
    public const LOG_ID = 'log_id';
    public const SUBSCRIPTION_ID = 'subscription_id';
    public const RUN_DATE = 'run_date';
    public const OUTCOME = 'outcome';
    public const ORDER_ID = 'order_id';
    public const FAILURE_REASON = 'failure_reason';

    public const OUTCOME_SUCCESS = 'success';
    public const OUTCOME_FAILURE = 'failure';
    public const OUTCOME_SKIPPED = 'skipped';

    /**
     * @return int|null
     */
    public function getLogId(): ?int;

    /**
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): self;

    /**
     * @return int
     */
    public function getSubscriptionId(): int;

    /**
     * @param int $subscriptionId
     * @return $this
     */
    public function setSubscriptionId(int $subscriptionId): self;

    /**
     * @return string
     */
    public function getRunDate(): string;

    /**
     * @param string $runDate
     * @return $this
     */
    public function setRunDate(string $runDate): self;

    /**
     * @return string
     */
    public function getOutcome(): string;

    /**
     * @param string $outcome
     * @return $this
     */
    public function setOutcome(string $outcome): self;

    /**
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self;

    /**
     * @return string|null
     */
    public function getFailureReason(): ?string;

    /**
     * @param string|null $failureReason
     * @return $this
     */
    public function setFailureReason(?string $failureReason): self;
}
