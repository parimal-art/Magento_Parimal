<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model\Subscription;

use Codilar\CoffeeClub\Api\Data\RunLogInterface;
use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Api\RunLogRepositoryInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Codilar\CoffeeClub\Model\ResourceModel\Subscription\CollectionFactory;
use Codilar\CoffeeClub\Model\RunLogFactory;
use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\RegionInterface;
use Magento\Directory\Model\ResourceModel\Region\CollectionFactory as RegionCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Quote\Model\QuoteFactory;
use Magento\Quote\Model\QuoteManagement;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;
use Throwable;

/**
 * Cron-driven engine that fulfils due subscriptions by creating Magento orders.
 *
 * Emails are delegated to EmailNotifier — this class no longer builds any
 * messages directly. This is the single place where "what happened during a run"
 * is decided; EmailNotifier only cares about "how to send".
 */
class Fulfilment
{
    private const CONFIG_PATH_FAILURE_THRESHOLD = 'coffeeclub/general/failure_threshold';
    private const CONFIG_PATH_SHIPPING_METHOD   = 'coffeeclub/general/shipping_method';
    private const CONFIG_PATH_PAYMENT_METHOD    = 'coffeeclub/general/payment_method';

    public function __construct(
        protected CollectionFactory               $collectionFactory,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected RunLogRepositoryInterface       $runLogRepository,
        protected RunLogFactory                   $runLogFactory,
        protected ProductRepositoryInterface      $productRepository,
        protected CustomerRepositoryInterface     $customerRepository,
        protected AddressRepositoryInterface      $addressRepository,
        protected RegionCollectionFactory         $regionCollectionFactory,
        protected QuoteFactory                    $quoteFactory,
        protected QuoteManagement                 $quoteManagement,
        protected OrderRepositoryInterface        $orderRepository,
        protected ScopeConfigInterface            $scopeConfig,
        protected DateTime                        $dateTime,
        protected LoggerInterface                 $logger,
        protected StoreManagerInterface           $storeManager,
        protected FrequencyProvider               $frequencyProvider,
        protected EmailNotifier                   $emailNotifier
    ) {
    }

    public function processDueSubscriptions(): int
    {
        $this->logger->info('CoffeeClub DEBUG: RUN START');

        if (!$this->scopeConfig->isSetFlag(
            'coffeeclub/general/enabled',
            ScopeInterface::SCOPE_STORE
        )) {
            $this->logger->info('CoffeeClub DEBUG: Module disabled in configuration.');
            return 0;
        }

        $today = $this->dateTime->gmtDate('Y-m-d');
        $this->logger->info('CoffeeClub DEBUG: Today (GMT) = ' . $today);

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', SubscriptionInterface::STATUS_ACTIVE)
            ->addFieldToFilter('next_due_date', ['lteq' => $today]);

        $this->logger->info(
            'CoffeeClub DEBUG: Found ' . $collection->getSize() . ' due subscription(s).'
        );

        $processed = 0;

        foreach ($collection as $subscription) {
            try {
                $this->logger->info(sprintf(
                    'CoffeeClub DEBUG: --- Processing subscription #%d ---',
                    (int) $subscription->getSubscriptionId()
                ));
                $this->processSingleSubscription($subscription);
                $processed++;
            } catch (Throwable $exception) {
                $this->logger->error(sprintf(
                    'CoffeeClub DEBUG: FATAL for subscription #%d | %s | %s | %s:%d',
                    (int) $subscription->getSubscriptionId(),
                    get_class($exception),
                    $exception->getMessage(),
                    $exception->getFile(),
                    $exception->getLine()
                ));
            }
        }

        $this->logger->info(sprintf(
            'CoffeeClub DEBUG: ========== RUN END. Processed %d ==========',
            $processed
        ));

        return $processed;
    }

    private function processSingleSubscription(SubscriptionInterface $subscription): void
    {
        $productId  = $subscription->getProductId();
        $customerId = $subscription->getCustomerId();

        try {
            $product = $this->productRepository->getById($productId);
        } catch (Throwable $exception) {
            $this->logger->error('CoffeeClub DEBUG: Product load failed: ' . $exception->getMessage());
            $this->pauseAndNotify($subscription, sprintf('Product %d could not be loaded.', $productId));
            return;
        }

        $this->logger->info(sprintf(
            'CoffeeClub DEBUG: Product loaded. SKU=%s | Type=%s | Salable=%s',
            $product->getSku(),
            $product->getTypeId(),
            $product->isSalable() ? 'YES' : 'NO'
        ));

        if (!$product->isSalable() || (int) $product->getStatus() !== 1) {
            $this->pauseAndNotify($subscription, 'Product is no longer available.');
            return;
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
        } catch (Throwable $exception) {
            $this->saveRunLog(
                $subscription,
                RunLogInterface::OUTCOME_FAILURE,
                null,
                'Customer load failed: ' . $exception->getMessage()
            );
            return;
        }

        try {
            $order = $this->createOrder($subscription, $product, $customer);

            $this->logger->info(sprintf(
                'CoffeeClub DEBUG: createOrder() SUCCESS. Order ID=%d | Increment ID=%s',
                (int) $order->getEntityId(),
                $order->getIncrementId()
            ));

            $subscription->setLastRunDate($this->dateTime->gmtDate('Y-m-d'));
            $subscription->setNextDueDate(
                $this->calculateNextDueDateFromNow($subscription->getFrequency())
            );
            $subscription->setConsecutiveFailureCount(0);
            $saved = $this->subscriptionRepository->save($subscription);

            $this->saveRunLog(
                $saved,
                RunLogInterface::OUTCOME_SUCCESS,
                (int) $order->getEntityId(),
                null
            );

            // Notify the customer that their subscription order was created.
            $this->emailNotifier->notifyOrderSuccess($saved, $order);
        } catch (Throwable $exception) {
            $this->logger->error(sprintf(
                'CoffeeClub DEBUG: createOrder() FAILED | %s | %s',
                get_class($exception),
                $exception->getMessage()
            ));

            $failureCount = $subscription->getConsecutiveFailureCount() + 1;
            $subscription->setConsecutiveFailureCount($failureCount);

            $thresholdReached = $failureCount >= $this->getFailureThreshold();

            if ($thresholdReached) {
                $subscription->setStatus(SubscriptionInterface::STATUS_PAUSED);
            }

            $saved = $this->subscriptionRepository->save($subscription);

            $this->saveRunLog(
                $saved,
                RunLogInterface::OUTCOME_FAILURE,
                null,
                $exception->getMessage()
            );

            // Send exactly ONE email:
            //  - If the failure threshold was reached, the dedicated "consecutive failures" email.
            //  - Otherwise, the standard single-failure email.
            if ($thresholdReached) {
                $this->emailNotifier->notifyConsecutiveFailures(
                    $saved,
                    $failureCount,
                    'Consecutive fulfilment attempts failed. Your subscription has been paused.'
                );
            } else {
                $this->emailNotifier->notifyOrderFailure($saved, $exception->getMessage());
            }
        }
    }

    private function createOrder(
        SubscriptionInterface                        $subscription,
        ProductInterface                             $product,
        \Magento\Customer\Api\Data\CustomerInterface $customer
    ): OrderInterface {
        $store = $this->storeManager->getStore((int) $customer->getStoreId());

        $quote = $this->quoteFactory->create();
        $quote->setStore($store);
        $quote->setStoreId((int) $store->getId());
        $quote->setCustomer($customer);
        $quote->setCustomerIsGuest(false);
        $quote->setCustomerEmail($customer->getEmail());

        $quoteItem = $quote->addProduct($product, $subscription->getQuantity());
        if (is_string($quoteItem)) {
            throw new LocalizedException(__('addProduct failed: %1', $quoteItem));
        }

        $quoteItem->setCustomPrice($subscription->getAgreedPrice());
        $quoteItem->setOriginalCustomPrice($subscription->getAgreedPrice());
        $quoteItem->getProduct()->setIsSuperMode(true);

        $addressData = $this->unserializeAddress($subscription->getDeliveryAddress());
        $addressData = $this->resolveRegion($addressData, (int) $subscription->getCustomerId());

        $quote->getBillingAddress()->addData($addressData);
        $quote->getShippingAddress()->addData($addressData);

        $quote->getShippingAddress()->setCollectShippingRates(true);
        $quote->getShippingAddress()->collectShippingRates();

        $rates = $quote->getShippingAddress()->getAllShippingRates();
        $this->logger->info(sprintf(
            'CoffeeClub DEBUG: Available shipping rates: %d',
            count($rates)
        ));

        $quote->getShippingAddress()->setShippingMethod($this->getShippingMethod());

        $payment = $quote->getPayment();
        $payment->setQuote($quote);
        $payment->setStore($store);
        $payment->setStoreId((int) $store->getId());

        $quote->setPaymentMethod($this->getPaymentMethod());
        $quote->setInventoryProcessed(false);
        $payment->importData(['method' => $this->getPaymentMethod()]);

        $quote->collectTotals()->save();

        $order = $this->quoteManagement->submit($quote);
        if (!$order) {
            throw new LocalizedException(__('Quote submit returned null.'));
        }

        return $order;
    }

    private function resolveRegion(array $addressData, int $customerId): array
    {
        if (!empty($addressData['region_id'])) {
            return $addressData;
        }

        $countryId = $addressData['country_id'] ?? null;
        if (!$countryId) {
            return $addressData;
        }

        $regionName = $addressData['region'] ?? null;
        if ($regionName) {
            try {
                $collection = $this->regionCollectionFactory->create();
                $collection->addCountryFilter($countryId)
                    ->addRegionNameFilter($regionName);
                $region = $collection->getFirstItem();

                if ($region && $region->getId()) {
                    $addressData['region_id'] = (int) $region->getId();
                    $this->logger->info(sprintf(
                        'CoffeeClub DEBUG: [resolveRegion] Found region_id %d for "%s"',
                        (int) $region->getId(),
                        $regionName
                    ));
                    return $addressData;
                }
            } catch (Throwable $exception) {
                $this->logger->warning(
                    'CoffeeClub DEBUG: [resolveRegion] Name lookup failed: ' . $exception->getMessage()
                );
            }
        }

        try {
            $customer = $this->customerRepository->getById($customerId);
            $defaultShippingId = $customer->getDefaultShipping();

            if ($defaultShippingId) {
                $defaultAddress = $this->addressRepository->getById((int) $defaultShippingId);

                if ($defaultAddress->getCountryId() === $countryId
                    && $defaultAddress->getRegionId()
                ) {
                    $addressData['region_id'] = (int) $defaultAddress->getRegionId();

                    $region = $defaultAddress->getRegion();
                    if ($region instanceof RegionInterface) {
                        $addressData['region'] = (string) $region->getRegion();
                    } elseif (is_string($region)) {
                        $addressData['region'] = $region;
                    }

                    $this->logger->info(sprintf(
                        'CoffeeClub DEBUG: [resolveRegion] Copied region_id %d from customer default',
                        (int) $defaultAddress->getRegionId()
                    ));
                    return $addressData;
                }
            }
        } catch (Throwable $exception) {
            $this->logger->warning(
                'CoffeeClub DEBUG: [resolveRegion] Default address lookup failed: '
                . $exception->getMessage()
            );
        }

        $this->logger->warning(
            'CoffeeClub DEBUG: [resolveRegion] Could not resolve region_id for country ' . $countryId
        );

        return $addressData;
    }

    /**
     * Pause the subscription due to a system reason (product gone/disabled)
     * and notify the customer.
     */
    private function pauseAndNotify(SubscriptionInterface $subscription, string $reason): void
    {
        $subscription->setStatus(SubscriptionInterface::STATUS_PAUSED);
        $saved = $this->subscriptionRepository->save($subscription);

        $this->saveRunLog($saved, RunLogInterface::OUTCOME_FAILURE, null, $reason);

        // Uses the paused template, with the specific system reason.
        $this->emailNotifier->notifyPaused($saved, $reason);
    }

    private function saveRunLog(
        SubscriptionInterface $subscription,
        string                $outcome,
        ?int                  $orderId,
        ?string               $reason
    ): void {
        try {
            $runLog = $this->runLogFactory->create();
            $runLog->setSubscriptionId((int) $subscription->getSubscriptionId());
            $runLog->setRunDate($this->dateTime->gmtDate('Y-m-d H:i:s'));
            $runLog->setOutcome($outcome);
            $runLog->setOrderId($orderId);
            $runLog->setFailureReason($reason);
            $this->runLogRepository->save($runLog);
        } catch (Throwable $exception) {
            $this->logger->error(
                'CoffeeClub DEBUG: Failed to save run log: ' . $exception->getMessage()
            );
        }
    }

    /**
     * Calculate the next due date using the admin-configured frequency modifier.
     *
     * @param string $frequency Frequency code (e.g. "monthly", "weekly").
     * @return string           Next date in Y-m-d format.
     * @throws LocalizedException When the frequency is not configured.
     */
    private function calculateNextDueDateFromNow(string $frequency): string
    {
        if (!$this->frequencyProvider->isValid($frequency)) {
            throw new LocalizedException(
                __('Frequency "%1" is not configured.', $frequency)
            );
        }

        return $this->frequencyProvider->calculateNextDate($frequency);
    }

    private function unserializeAddress(string $serialized): array
    {
        $data = json_decode($serialized, true);
        if (!is_array($data)) {
            $data = @unserialize($serialized);
        }
        if (!is_array($data)) {
            return [];
        }
        if (isset($data['street']) && is_string($data['street'])) {
            $data['street'] = [$data['street']];
        }
        return $data;
    }

    /**
     * @return int
     */
    private function getFailureThreshold(): int
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_FAILURE_THRESHOLD,
            ScopeInterface::SCOPE_STORE
        );

        return max(1, (int) $value);
    }

    /**
     * @return string
     */
    private function getShippingMethod(): string
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_SHIPPING_METHOD,
            ScopeInterface::SCOPE_STORE
        );

        return $value ?: 'flatrate_flatrate';
    }

    /**
     * @return string
     */
    private function getPaymentMethod(): string
    {
        $value = $this->scopeConfig->getValue(
            self::CONFIG_PATH_PAYMENT_METHOD,
            ScopeInterface::SCOPE_STORE
        );

        return $value ?: 'checkmo';
    }
}
