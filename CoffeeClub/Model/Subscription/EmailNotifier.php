<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Subscription;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\App\Area;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Sales\Api\Data\OrderInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Central email notification service for Coffee Club subscriptions.
 *
 * Every transactional email the module sends goes through this class.
 * Each public method corresponds to one business event.
 *
 * IMPORTANT:
 *  - This class never throws. A failed email must never break a business action.
 *  - All failures are logged via LoggerInterface for later inspection.
 *  - Actual SMTP transport is provided by the configured SMTP module
 *    (e.g. Mageplaza SMTP). This class only builds the message.
 */
class EmailNotifier
{
    /**
     * Template identifiers.
     * These MUST match the "id" attribute in etc/email_templates.xml.
     */
    public const TEMPLATE_STARTED              = 'codilar_coffeeclub_subscription_started';
    public const TEMPLATE_PAUSED               = 'codilar_coffeeclub_subscription_paused';
    public const TEMPLATE_RESUMED              = 'codilar_coffeeclub_subscription_resumed';
    public const TEMPLATE_SKIPPED              = 'codilar_coffeeclub_subscription_skipped';
    public const TEMPLATE_CANCELLED            = 'codilar_coffeeclub_subscription_cancelled';
    public const TEMPLATE_ORDER_SUCCESS        = 'codilar_coffeeclub_subscription_order_success';
    public const TEMPLATE_ORDER_FAILURE        = 'codilar_coffeeclub_subscription_order_failure';
    public const TEMPLATE_CONSECUTIVE_FAILURES = 'codilar_coffeeclub_subscription_consecutive_failures';

    /**
     * Sender identity scope.
     *
     * 'general' maps to:
     *   Stores > Configuration > General > Store Email Addresses > General Contact.
     *
     * The actual SMTP credentials come from Mageplaza SMTP; this only sets
     * the "From" header and lets Magento resolve the sender at the correct store scope.
     */
    private const SENDER_SCOPE = 'general';

    public function __construct(
        protected TransportBuilder            $transportBuilder,
        protected CustomerRepositoryInterface $customerRepository,
        protected ProductRepositoryInterface  $productRepository,
        protected FrequencyProvider           $frequencyProvider,
        protected LoggerInterface             $logger
    ) {
    }

    /**
     * Send the "subscription started" email.
     */
    public function notifyStarted(SubscriptionInterface $subscription): void
    {
        $this->send(self::TEMPLATE_STARTED, $subscription, [
            'next_delivery_date' => (string) ($subscription->getNextDueDate() ?? ''),
        ]);
    }

    /**
     * Send the "subscription paused" email.
     *
     * Used for:
     *   - Customer-initiated pause (reason defaults to "Paused at your request.")
     *   - System pause when the product is no longer available
     *
     * NOT used for consecutive failure threshold — that has its own dedicated email.
     */
    public function notifyPaused(SubscriptionInterface $subscription, string $reason = ''): void
    {
        $this->send(self::TEMPLATE_PAUSED, $subscription, [
            'reason' => $reason !== '' ? $reason : 'Paused at your request.',
        ]);
    }

    /**
     * Send the "subscription resumed" email.
     */
    public function notifyResumed(SubscriptionInterface $subscription): void
    {
        $this->send(self::TEMPLATE_RESUMED, $subscription, [
            'next_delivery_date' => (string) ($subscription->getNextDueDate() ?? ''),
        ]);
    }

    /**
     * Send the "next delivery skipped" email.
     *
     * @param string|null $skippedDate The date that was skipped (Y-m-d).
     */
    public function notifySkipped(SubscriptionInterface $subscription, ?string $skippedDate = null): void
    {
        $this->send(self::TEMPLATE_SKIPPED, $subscription, [
            'skipped_date'       => (string) ($skippedDate ?? ''),
            'next_delivery_date' => (string) ($subscription->getNextDueDate() ?? ''),
        ]);
    }

    /**
     * Send the "subscription cancelled" email.
     */
    public function notifyCancelled(SubscriptionInterface $subscription): void
    {
        $this->send(self::TEMPLATE_CANCELLED, $subscription, []);
    }

    /**
     * Send the "subscription order succeeded" email.
     */
    public function notifyOrderSuccess(SubscriptionInterface $subscription, OrderInterface $order): void
    {
        $this->send(self::TEMPLATE_ORDER_SUCCESS, $subscription, [
            'order_increment_id' => (string) $order->getIncrementId(),
            'order_total'        => number_format((float) $order->getBaseGrandTotal(), 2, '.', ''),
            'next_delivery_date' => (string) ($subscription->getNextDueDate() ?? ''),
        ]);
    }

    /**
     * Send the "subscription order failed" email (single failure).
     */
    public function notifyOrderFailure(SubscriptionInterface $subscription, string $reason): void
    {
        $this->send(self::TEMPLATE_ORDER_FAILURE, $subscription, [
            'failure_reason' => $reason,
            'failure_count'  => (int) $subscription->getConsecutiveFailureCount(),
        ]);
    }

    /**
     * Send the dedicated "consecutive failures threshold reached" email.
     *
     * This is separate from notifyPaused() on purpose: it informs the customer
     * that repeated failures caused the subscription to be paused.
     */
    public function notifyConsecutiveFailures(
        SubscriptionInterface $subscription,
        int                   $failureCount,
        string                $reason
    ): void {
        $this->send(self::TEMPLATE_CONSECUTIVE_FAILURES, $subscription, [
            'failure_count'  => $failureCount,
            'failure_reason' => $reason,
        ]);
    }

    /**
     * Internal helper: build and send one email.
     *
     * @param string                $templateId   Template ID registered in email_templates.xml.
     * @param SubscriptionInterface $subscription Used to load the customer and dynamic data.
     * @param array<string, mixed>  $vars         Extra template variables for this specific email.
     */
    private function send(string $templateId, SubscriptionInterface $subscription, array $vars): void
    {
        // 1. Load the customer. If this fails, we cannot determine recipient/store — log and return.
        try {
            $customer = $this->customerRepository->getById($subscription->getCustomerId());
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'CoffeeClub email [%s]: could not load customer #%d — %s',
                $templateId,
                (int) $subscription->getCustomerId(),
                $exception->getMessage()
            ));
            return;
        }

        $storeId = (int) $customer->getStoreId();

        // 2. Merge common variables (product name, frequency label, etc.) with event-specific ones.
        $templateVars = array_merge($this->buildBaseVars($subscription, $customer), $vars);

        // 3. Build the transport and send. Any failure is logged — never re-thrown.
        try {
            $transport = $this->transportBuilder
                ->setTemplateIdentifier($templateId)
                ->setTemplateOptions([
                    'area'  => Area::AREA_FRONTEND,
                    'store' => $storeId,
                ])
                ->setTemplateVars($templateVars)
                ->setFromByScope(self::SENDER_SCOPE, $storeId)
                ->addTo((string) $customer->getEmail(), (string) $customer->getFirstname())
                ->getTransport();

            $transport->sendMessage();

            $this->logger->info(sprintf(
                'CoffeeClub email [%s]: sent to %s (store %d).',
                $templateId,
                (string) $customer->getEmail(),
                $storeId
            ));
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'CoffeeClub email [%s]: send failed for customer #%d — %s',
                $templateId,
                (int) $subscription->getCustomerId(),
                $exception->getMessage()
            ));
        }
    }

    /**
     * Build the variables every template can rely on.
     *
     * @param \Magento\Customer\Api\Data\CustomerInterface $customer
     * @return array<string, mixed>
     */
    private function buildBaseVars(SubscriptionInterface $subscription, $customer): array
    {
        return [
            'customer_name'   => (string) $customer->getFirstname(),
            'subscription_id' => (int) $subscription->getSubscriptionId(),
            'product_name'    => $this->resolveProductName($subscription),
            'quantity'        => (int) $subscription->getQuantity(),
            'frequency_label' => $this->frequencyProvider->getLabel((string) $subscription->getFrequency()),
            'agreed_price'    => number_format((float) $subscription->getAgreedPrice(), 2, '.', ''),
            'status'          => (string) $subscription->getStatus(),
        ];
    }

    /**
     * Resolve a human-readable product name.
     * Falls back to "Product #ID" if the product was deleted, so the email
     * still renders instead of failing.
     */
    private function resolveProductName(SubscriptionInterface $subscription): string
    {
        try {
            $product = $this->productRepository->getById((int) $subscription->getProductId());
            return (string) $product->getName();
        } catch (Throwable $exception) {
            return sprintf('Product #%d', (int) $subscription->getProductId());
        }
    }
}
