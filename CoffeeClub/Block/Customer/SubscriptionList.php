<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Block\Customer;

use Codilar\CoffeeClub\Api\RunLogRepositoryInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\Config\FrequencyProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;

class SubscriptionList extends Template
{
    public function __construct(
        Template\Context                          $context,
        protected CustomerSession                 $customerSession,
        protected SubscriptionRepositoryInterface $subscriptionRepository,
        protected RunLogRepositoryInterface       $runLogRepository,
        protected SearchCriteriaBuilder           $searchCriteriaBuilder,
        protected FrequencyProvider               $frequencyProvider,
        array                                     $data = []
    )
    {
        parent::__construct($context, $data);
    }

    public function getSubscriptions(): array
    {
        $customerId = (int)$this->customerSession->getCustomerId();
        if (!$customerId) {
            return [];
        }

        $searchCriteria = $this->searchCriteriaBuilder
            ->addFilter('customer_id', $customerId)
            ->create();

        $result = $this->subscriptionRepository->getList($searchCriteria);
        return $result->getItems();
    }

    public function getSubscriptionHistory(int $subscriptionId, int $limit = 10): array
    {
        return $this->runLogRepository->getBySubscriptionId($subscriptionId, $limit);
    }

    public function hasSubscriptions(): bool
    {
        return !empty($this->getSubscriptions());
    }

    public function getActionUrl(string $action, int $subscriptionId): string
    {
        return $this->getUrl('coffeeclub/subscription/' . $action, [
            'subscription_id' => $subscriptionId
        ]);
    }

    public function getFrequencyLabel(string $frequency): Phrase
    {
        return __($this->frequencyProvider->getLabel($frequency));
    }

    public function getStatusLabel(string $status): Phrase
    {
        return match ($status) {
            'active' => __('Active'),
            'paused' => __('Paused'),
            'cancelled' => __('Cancelled'),
            default => __('Unknown'),
        };
    }
}
