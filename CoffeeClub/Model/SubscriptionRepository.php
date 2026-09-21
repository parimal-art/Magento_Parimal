<?php
declare(strict_types=1);

namespace Codilar\CoffeeClub\Model;

use Codilar\CoffeeClub\Api\Data\SubscriptionInterface;
use Codilar\CoffeeClub\Api\SubscriptionRepositoryInterface;
use Codilar\CoffeeClub\Model\ResourceModel\Subscription as SubscriptionResource;
use Codilar\CoffeeClub\Model\ResourceModel\Subscription\CollectionFactory;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

class SubscriptionRepository implements SubscriptionRepositoryInterface
{
    public function __construct(
        protected SubscriptionResource $resource,
        protected SubscriptionFactory $subscriptionFactory,
        protected CollectionFactory $collectionFactory,
        protected SearchResultsInterfaceFactory $searchResultsFactory,
        protected CollectionProcessorInterface $collectionProcessor
    ) {
    }

    /**
     * @inheritDoc
     */
    public function save(SubscriptionInterface $subscription): SubscriptionInterface
    {
        try {
            /** @var Subscription $subscription */
            $this->resource->save($subscription);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the subscription: %1', $exception->getMessage()),
                $exception
            );
        }

        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function getById(int $subscriptionId): SubscriptionInterface
    {
        $subscription = $this->subscriptionFactory->create();
        $this->resource->load($subscription, $subscriptionId);

        if (!$subscription->getId()) {
            throw new NoSuchEntityException(
                __('Subscription with ID "%1" does not exist.', $subscriptionId)
            );
        }

        return $subscription;
    }

    /**
     * @inheritDoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): \Magento\Framework\Api\SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();
        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(SubscriptionInterface $subscription): bool
    {
        try {
            /** @var Subscription $subscription */
            $this->resource->delete($subscription);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the subscription: %1', $exception->getMessage()),
                $exception
            );
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteById(int $subscriptionId): bool
    {
        return $this->delete($this->getById($subscriptionId));
    }
}
