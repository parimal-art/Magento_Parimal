<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Subscription;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Magento\Framework\Exception\LocalizedException;

/**
 * Business logic for customer-initiated subscription actions.
 *
 * Emails are sent AFTER the database save succeeds. If save fails,
 * no email is sent — the customer is only notified about actions that
 * actually took effect.
 */
class SubscriptionManagement
{
    public function __construct(
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected FrequencyProvider               $frequencyProvider,
        protected EmailNotifier                   $emailNotifier
    ) {
    }

    /**
     * Pause an active subscription.
     *
     * @throws LocalizedException
     */
    public function pause(int $subscriptionId, int $customerId): SubscriptionInterface
    {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $this->assertCustomerOwnsSubscription($subscription, $customerId);
        $this->assertStatusIsActive($subscription);

        $subscription->setStatus(SubscriptionInterface::STATUS_PAUSED);
        $saved = $this->subscriptionRepository->save($subscription);

        // Notify the customer after a successful save.
        $this->emailNotifier->notifyPaused($saved);

        return $saved;
    }

    /**
     * Resume a paused subscription.
     *
     * @throws LocalizedException
     */
    public function resume(int $subscriptionId, int $customerId): SubscriptionInterface
    {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $this->assertCustomerOwnsSubscription($subscription, $customerId);

        if ($subscription->getStatus() !== SubscriptionInterface::STATUS_PAUSED) {
            throw new LocalizedException(__('Only paused subscriptions can be resumed.'));
        }

        $subscription->setStatus(SubscriptionInterface::STATUS_ACTIVE);
        $subscription->setConsecutiveFailureCount(0);
        $subscription->setNextDueDate(
            $this->calculateNextDueDate($subscription->getFrequency())
        );

        $saved = $this->subscriptionRepository->save($subscription);

        // Notify the customer after a successful save.
        $this->emailNotifier->notifyResumed($saved);

        return $saved;
    }

    /**
     * Skip the next scheduled delivery.
     *
     * @throws LocalizedException
     */
    public function skipNext(int $subscriptionId, int $customerId): SubscriptionInterface
    {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $this->assertCustomerOwnsSubscription($subscription, $customerId);
        $this->assertStatusIsActive($subscription);

        // Capture the date that is being skipped BEFORE we change it.
        // This is the date we tell the customer about in the email.
        $skippedDate = $subscription->getNextDueDate();

        $subscription->setNextDueDate(
            $this->calculateNextDueDate(
                $subscription->getFrequency(),
                $subscription->getNextDueDate()
            )
        );

        $saved = $this->subscriptionRepository->save($subscription);

        // Notify the customer after a successful save.
        $this->emailNotifier->notifySkipped($saved, $skippedDate);

        return $saved;
    }

    /**
     * Cancel a subscription.
     *
     * @throws LocalizedException
     */
    public function cancel(int $subscriptionId, int $customerId): SubscriptionInterface
    {
        $subscription = $this->subscriptionRepository->getById($subscriptionId);
        $this->assertCustomerOwnsSubscription($subscription, $customerId);

        $subscription->setStatus(SubscriptionInterface::STATUS_CANCELLED);
        $saved = $this->subscriptionRepository->save($subscription);

        // Notify the customer after a successful save.
        $this->emailNotifier->notifyCancelled($saved);

        return $saved;
    }

    /**
     * Calculate the next due date based on the configured frequency.
     *
     * @throws LocalizedException
     */
    public function calculateNextDueDate(string $frequency, ?string $baseDate = null): string
    {
        if (!$this->frequencyProvider->isValid($frequency)) {
            throw new LocalizedException(
                __('Frequency "%1" is not configured.', $frequency)
            );
        }

        return $this->frequencyProvider->calculateNextDate($frequency, $baseDate);
    }

    /**
     * @throws LocalizedException
     */
    private function assertCustomerOwnsSubscription(
        SubscriptionInterface $subscription,
        int                   $customerId
    ): void {
        if ($subscription->getCustomerId() !== $customerId) {
            throw new LocalizedException(
                __('You do not have permission to manage this subscription.')
            );
        }
    }

    /**
     * @throws LocalizedException
     */
    private function assertStatusIsActive(SubscriptionInterface $subscription): void
    {
        if ($subscription->getStatus() !== SubscriptionInterface::STATUS_ACTIVE) {
            throw new LocalizedException(
                __('Only active subscriptions can be modified.')
            );
        }
    }
}
